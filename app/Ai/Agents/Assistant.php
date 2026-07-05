<?php

namespace App\Ai\Agents;

use App\Ai\Tools\AssignTask;
use App\Ai\Tools\CreateTask;
use App\Ai\Tools\FindTasks;
use App\Ai\Tools\GetAppRoutes;
use App\Ai\Tools\GetProjectSummary;
use App\Ai\Tools\ListMyTasks;
use App\Ai\Tools\UpdateTaskDetails;
use App\Ai\Tools\UpdateTaskStatus;
use App\Models\User;
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
 * TeamHUB conversational assistant focused on workspaces, projects, and tasks.
 *
 * The agent is always constructed for a specific authenticated user and only
 * exposes task/project tools appropriate to that user's visibility and
 * management scope.
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
            : 'المستخدم الحالي زائر غير مسجّل الدخول، لذلك لا يمكنه الوصول إلى المهام أو المشاريع الشخصية. إن سأل عن عمله أو أراد تنفيذ إجراء، فادعُه بلطف إلى تسجيل الدخول.';

        return <<<PROMPT
        أنت "مساعد TeamHUB"، مساعد ذكي لإدارة العمل داخل المنصة.

        {$identity} التاريخ والوقت الآن: {$today}.

        قواعد أساسية:
        - أجب دائمًا بنفس لغة رسالة المستخدم (العربية افتراضًا، والإنجليزية إن كتب بالإنجليزية).
        - اعتمد على الأدوات المتاحة لك للحصول على كل المعلومات الواقعية عن مساحات العمل والمشاريع والمهام وروابط التنقّل. لا تختلق أي بيانات أو أسماء أو أرقام أو روابط إطلاقًا.
        - صلاحياتك مطابقة لصلاحيات المستخدم. إذا أعادت إحدى الأدوات رسالة بعدم السماح، فأخبر المستخدم بلطف أن هذه المعلومات غير متاحة له دون كشف أي تفاصيل.
        - نسّق ردودك بصيغة Markdown (عناوين، قوائم، روابط، تمييز) لتسهيل القراءة.
        - كن موجزًا وواضحًا. عند ذكر مهمة أو مشروع أو مساحة عمل، أدرج الرابط الذي تعيده الأداة عند توفره.
        - إذا لم تُرجع الأداة أي نتائج، فأبلغ المستخدم بذلك بصدق بدلًا من التخمين.
        - ركّز على TeamHUB فقط: مساحات العمل والمشاريع والمهام وحالة التقدّم والمراجعة. لا تعد المستخدم بعمليات خارج هذا النطاق.

        إجراءات الكتابة (الأدوات التي تعدّل البيانات):
        - عند استخدام أداة كتابة (إنشاء مهمة، إسنادها، تعديل حالتها أو تفاصيلها...)، ستستقبل استجابة بحالة "pending_confirmation".
        - في هذه الحالة: اشرح للمستخدم بوضوح ما الذي ستقوم به، وأخبره أنه يحتاج للنقر على زر التأكيد الذي سيظهر في المحادثة.
        - لا تدّعِ أن الإجراء قد تنفّذ قبل أن يؤكد المستخدم. انتظر التأكيد.
        - إذا كانت المهمة ستنتقل إلى "review"، فتأكّد من وجود رابط مخرج أو ملاحظات تسليم على الأقل.
        - إذا أعادت الأداة خطأ (مثل عدم الصلاحية أو عدم العثور على مشروع/مهمة)، فأبلغ المستخدم بذلك مباشرة.
        PROMPT;
    }

    /**
     * Tools available to the current user. Guests only get generic navigation
     * help; authenticated users get task/project read tools; managers also get
     * confirm-before-write task mutations.
     *
     * @return array<int, Tool>
     */
    public function tools(): iterable
    {
        $tools = [
            new GetAppRoutes($this->user),
        ];

        if ($this->user === null) {
            return $tools;
        }

        $tools[] = new ListMyTasks($this->user);
        $tools[] = new FindTasks($this->user);
        $tools[] = new GetProjectSummary($this->user);
        $tools[] = new UpdateTaskStatus($this->user);

        if ($this->managesAnything()) {
            $tools[] = new CreateTask($this->user);
            $tools[] = new AssignTask($this->user);
            $tools[] = new UpdateTaskDetails($this->user);
        }

        return $tools;
    }

    /**
     * Whether the user oversees any club or committee, and so should be offered
     * the management reporting tools.
     */
    protected function managesAnything(): bool
    {
        return $this->user->isAdmin()
            || $this->user->managedWorkspaces()->isNotEmpty()
            || $this->user->managedProjects()->isNotEmpty();
    }
}
