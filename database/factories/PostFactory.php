<?php

namespace Database\Factories;

use App\Models\Club;
use App\Models\Post;
use App\Models\User;
use Database\Factories\Support\DemoCoverImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    /**
     * Realistic Arabic news headlines for club activity updates.
     *
     * @var list<string>
     */
    private const TITLES = [
        'النادي يختتم فعاليات الأسبوع التعريفي بنجاح',
        'فريق النادي يحصد المركز الأول في المسابقة الوطنية',
        'انطلاق التسجيل في البرنامج التدريبي الجديد',
        'تعاون مشترك بين النادي وعمادة شؤون الطلاب',
        'النادي يطلق مبادرة تطوعية لخدمة المجتمع',
        'تكريم الأعضاء المتميزين في الحفل السنوي',
    ];

    /**
     * Varied Arabic article bodies so the news feed does not repeat a single
     * paragraph across every post. Each is a few short paragraphs separated by
     * blank lines, matching how the article page renders content.
     *
     * @var list<string>
     */
    private const BODIES = [
        "نظّم النادي خلال الفترة الماضية مجموعة من الأنشطة التي لاقت تفاعلاً واسعاً من الطلاب، حيث شارك العشرات من الأعضاء في البرامج المختلفة.\n\nوأكد القائمون على النادي أن هذه الفعاليات تأتي ضمن خطة سنوية تهدف إلى تنمية مهارات الطلاب وتعزيز روح العمل الجماعي والمشاركة المجتمعية.\n\nوتوجّه النادي بالشكر لجميع المشاركين والداعمين، داعياً الطلاب إلى متابعة الإعلانات القادمة والانضمام إلى الأنشطة المقبلة.",
        "في إنجاز يُحسب لطلاب الجامعة، تمكّن فريق النادي من تحقيق نتيجة متميزة بعد أسابيع من التحضير والعمل الدؤوب على المشروع.\n\nوأشاد المشرفون بروح الفريق والالتزام الذي أظهره الأعضاء طوال مراحل الإعداد، مؤكدين أن هذا النجاح ثمرة جهد جماعي مخلص.\n\nويأمل الفريق أن يكون هذا الإنجاز حافزاً لمزيد من المشاركات في المحافل القادمة على المستويين المحلي والوطني.",
        "أعلن النادي عن فتح باب التسجيل في برنامجه التدريبي الجديد، الموجّه لجميع طلاب الجامعة الراغبين في تطوير مهاراتهم.\n\nويتضمّن البرنامج سلسلة من ورش العمل العملية التي يقدّمها مختصون، مع منح شهادات حضور معتمدة للمشاركين الملتزمين.\n\nوتُعد المقاعد محدودة، لذا يُنصح بالتسجيل المبكر عبر المنصة لضمان الحصول على مقعد قبل اكتمال العدد.",
        "وقّع النادي اتفاقية تعاون مع عمادة شؤون الطلاب تهدف إلى تنظيم أنشطة مشتركة تخدم الطلاب وتثري حياتهم الجامعية.\n\nوبموجب الاتفاقية، سيتم توفير الدعم اللوجستي والإرشادي للفعاليات القادمة، بما يعزز من جودة البرامج المقدمة.\n\nوعبّر الطرفان عن تطلعهما لشراكة مثمرة تسهم في بناء بيئة طلابية محفّزة وداعمة للإبداع.",
        "أطلق النادي مبادرة تطوعية جديدة تستهدف خدمة المجتمع المحلي والمساهمة في معالجة عدد من التحديات بمشاركة طلابية واسعة.\n\nودعا النادي جميع الطلاب المهتمين إلى الانضمام للمبادرة، مع احتساب ساعات تطوعية معتمدة لكل مشارك فيها.\n\nوتأتي هذه المبادرة انطلاقاً من رسالة النادي في غرس قيم العطاء والمسؤولية المجتمعية لدى الطلاب.",
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'club_id' => Club::factory(),
            'user_id' => User::factory(),
            'title' => fake()->randomElement(self::TITLES),
            'body' => fake()->randomElement(self::BODIES),
            'published_at' => now()->subDays(rand(1, 30)),
        ];
    }

    /**
     * Attach the given number of generated cover images to the created post,
     * tinted with the club's theme color so the article art matches its club.
     */
    public function withImages(int $count = 1): static
    {
        return $this->afterCreating(function (Post $post) use ($count): void {
            for ($i = 0; $i < $count; $i++) {
                $bytes = DemoCoverImage::generate("post-{$post->id}-{$i}", $post->club?->theme);

                $post->addMediaFromString($bytes)
                    ->usingFileName("cover-{$i}.jpg")
                    ->toMediaCollection(Post::IMAGE_COLLECTION);
            }
        });
    }
}
