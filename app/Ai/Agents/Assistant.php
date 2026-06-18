<?php

namespace App\Ai\Agents;

use App\Ai\Tools\AddClubMember;
use App\Ai\Tools\AddCommitteeMember;
use App\Ai\Tools\ApplyToClub;
use App\Ai\Tools\ApplyToCommittee;
use App\Ai\Tools\ApproveClubApplication;
use App\Ai\Tools\ApproveCommitteeApplication;
use App\Ai\Tools\CancelEventRegistration;
use App\Ai\Tools\CreateEvent;
use App\Ai\Tools\CreateNews;
use App\Ai\Tools\DeleteEvent;
use App\Ai\Tools\DeleteNews;
use App\Ai\Tools\FindClubs;
use App\Ai\Tools\FindCommittees;
use App\Ai\Tools\FindEvents;
use App\Ai\Tools\FindResources;
use App\Ai\Tools\GetAppRoutes;
use App\Ai\Tools\GetClubInfo;
use App\Ai\Tools\GetClubMembers;
use App\Ai\Tools\GetClubPendingApplications;
use App\Ai\Tools\GetClubReport;
use App\Ai\Tools\GetCommitteeInfo;
use App\Ai\Tools\GetCommitteeReport;
use App\Ai\Tools\GetEventDetails;
use App\Ai\Tools\GetMyApplications;
use App\Ai\Tools\GetMyAttendance;
use App\Ai\Tools\GetMyCertificates;
use App\Ai\Tools\GetMyClubs;
use App\Ai\Tools\GetMyCommittees;
use App\Ai\Tools\GetMyRegistrations;
use App\Ai\Tools\GetMyVolunteerHours;
use App\Ai\Tools\IssueCertificate;
use App\Ai\Tools\ListNews;
use App\Ai\Tools\LogVolunteerHours;
use App\Ai\Tools\RegisterForEvent;
use App\Ai\Tools\RejectClubApplication;
use App\Ai\Tools\RejectCommitteeApplication;
use App\Ai\Tools\RemoveClubMember;
use App\Ai\Tools\RemoveCommitteeMember;
use App\Ai\Tools\SearchCatalog;
use App\Ai\Tools\UpdateEvent;
use App\Ai\Tools\UpdateNews;
use App\Models\User;
use App\Services\CatalogSearch;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Read-only conversational assistant for the Ruwad platform.
 *
 * The agent is always constructed for a specific authenticated user and only
 * exposes tools appropriate to that user's role; every capability-gated tool
 * additionally re-authorizes against the user's club/committee Gates, so the
 * assistant can never surface data the user could not access themselves.
 */
#[MaxSteps(8)]
#[MaxTokens(4096)]
class Assistant implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    /**
     * Provider failover for the assistant: Gemini is the primary model, with
     * DeepSeek used only when Gemini is unavailable. Each provider is pinned to
     * its own model (the SDK fails over to the next entry on provider errors,
     * before any tokens have streamed).
     *
     * @return array<string, string>
     */
    public function provider(): array
    {
        // Preferred order, but only include providers that actually have an API
        // key configured. The SDK does not fail over on auth errors (e.g. a 403
        // from a missing key), so listing an unconfigured provider would hard-
        // fail every request; filtering keeps the assistant working wherever a
        // usable key exists (and auto-prefers Gemini once its key is set).
        $candidates = [
            'deepseek' => 'deepseek-reasoner',
            'gemini' => 'gemini-flash-latest',
        ];

        $configured = array_filter(
            $candidates,
            fn (string $name): bool => filled(config("ai.providers.{$name}.key")),
            ARRAY_FILTER_USE_KEY,
        );

        return $configured !== [] ? $configured : ['deepseek' => 'deepseek-reasoner'];
    }

    /**
     * The acting user, or null for an unauthenticated guest. Guests receive a
     * public-only tool set and no conversation memory.
     */
    public function __construct(protected ?User $user = null) {}

    public function instructions(): Stringable|string
    {
        $today = now()->toDayDateTimeString();

        $identity = $this->user !== null
            ? "المستخدم الحالي مسجّل الدخول باسم: {$this->user->name}."
            : 'المستخدم الحالي زائر غير مسجّل الدخول، فلا تتوفر له بيانات شخصية (التسجيلات، الشهادات، ساعات التطوع، التقارير). إن سأل عنها، فادعُه بلطف إلى تسجيل الدخول.';

        return <<<PROMPT
        أنت "مساعد رواد"، مساعد ذكي داخل منصة رواد للأندية والأنشطة الطلابية.

        {$identity} التاريخ والوقت الآن: {$today}.

        قواعد أساسية:
        - أجب دائمًا بنفس لغة رسالة المستخدم (العربية افتراضًا، والإنجليزية إن كتب بالإنجليزية).
        - اعتمد على الأدوات المتاحة لك للحصول على كل المعلومات الواقعية (الأندية، الفعاليات، الأخبار، وكذلك بيانات المستخدم إن كان مسجّلًا). لا تختلق أي بيانات أو أسماء أو أرقام أو روابط إطلاقًا.
        - صلاحياتك مطابقة لصلاحيات المستخدم. إذا أعادت إحدى الأدوات رسالة بعدم السماح، فأخبر المستخدم بلطف أن هذه المعلومات غير متاحة له دون كشف أي تفاصيل.
        - نسّق ردودك بصيغة Markdown (عناوين، قوائم، روابط، تمييز) لتسهيل القراءة.
        - كن موجزًا وواضحًا. عند ذكر فعالية أو خبر أو نادٍ، أدرج الرابط الذي تعيده الأداة عند توفره.
        - إذا لم تُرجع الأداة أي نتائج، فأبلغ المستخدم بذلك بصدق بدلًا من التخمين.

        إجراءات الكتابة (الأدوات التي تعدّل البيانات):
        - عند استخدام أداة كتابة (التسجيل، التقديم، الإنشاء، التعديل، الحذف...)، ستستقبل استجابة بحالة "pending_confirmation".
        - في هذه الحالة: اشرح للمستخدم بوضوح ما الذي ستقوم به، وأخبره أنه يحتاج للنقر على زر التأكيد الذي سيظهر في المحادثة.
        - لا تدّعِ أن الإجراء قد تنفّذ قبل أن يؤكد المستخدم. انتظر التأكيد.
        - إذا أعادت الأداة خطأ (مثل "السعة ممتلئة" أو "لستَ مخوّلًا")، فأبلغ المستخدم بذلك مباشرة.
        PROMPT;
    }

    /**
     * Tools available to the current user. Everyone gets the public catalog and
     * personal ("my …") tools; users who manage a club or committee also get
     * the management reporting tools, each still re-authorized per resource.
     *
     * @return array<int, Tool>
     */
    public function tools(): iterable
    {
        // Public-data tools — available to everyone, including guests.
        $tools = [
            new SearchCatalog($this->user, app(CatalogSearch::class)),
            new FindClubs($this->user),
            new FindEvents($this->user),
            new FindCommittees($this->user),
            new FindResources($this->user),
            new ListNews($this->user),
            new GetClubInfo($this->user),
            new GetCommitteeInfo($this->user),
            new GetEventDetails($this->user),
            new GetAppRoutes($this->user),
        ];

        if ($this->user === null) {
            return $tools;
        }

        // Personal ("my …") tools — only for authenticated users.
        $tools[] = new GetMyClubs($this->user);
        $tools[] = new GetMyCommittees($this->user);
        $tools[] = new GetMyRegistrations($this->user);
        $tools[] = new GetMyAttendance($this->user);
        $tools[] = new GetMyCertificates($this->user);
        $tools[] = new GetMyVolunteerHours($this->user);
        $tools[] = new GetMyApplications($this->user);

        // Student write tools — register, cancel, apply.
        $tools[] = new RegisterForEvent($this->user);
        $tools[] = new CancelEventRegistration($this->user);
        $tools[] = new ApplyToClub($this->user);
        $tools[] = new ApplyToCommittee($this->user);

        if ($this->managesAnything()) {
            // Management read tools.
            $tools[] = new GetClubMembers($this->user);
            $tools[] = new GetClubReport($this->user);
            $tools[] = new GetCommitteeReport($this->user);
            $tools[] = new GetClubPendingApplications($this->user);

            // Management write tools — members.
            $tools[] = new ApproveClubApplication($this->user);
            $tools[] = new RejectClubApplication($this->user);
            $tools[] = new AddClubMember($this->user);
            $tools[] = new RemoveClubMember($this->user);
            $tools[] = new ApproveCommitteeApplication($this->user);
            $tools[] = new RejectCommitteeApplication($this->user);
            $tools[] = new AddCommitteeMember($this->user);
            $tools[] = new RemoveCommitteeMember($this->user);

            // Management write tools — events.
            $tools[] = new CreateEvent($this->user);
            $tools[] = new UpdateEvent($this->user);
            $tools[] = new DeleteEvent($this->user);

            // Management write tools — news.
            $tools[] = new CreateNews($this->user);
            $tools[] = new UpdateNews($this->user);
            $tools[] = new DeleteNews($this->user);

            // Management write tools — volunteer hours & certificates.
            $tools[] = new LogVolunteerHours($this->user);
            $tools[] = new IssueCertificate($this->user);
        }

        return $tools;
    }

    /**
     * Whether the user oversees any club or committee, and so should be offered
     * the management reporting tools.
     */
    protected function managesAnything(): bool
    {
        return $this->user->isUniversityStaff()
            || $this->user->managedClubs()->isNotEmpty()
            || $this->user->managedCommittees()->isNotEmpty();
    }
}
