<?php

namespace App\Models;

use App\Enums\ClubCapability;
use App\Enums\ClubRole;
use App\Enums\CommitteeCapability;
use App\Enums\CommitteeRole;
use App\Enums\UserRole;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password', 'role', 'university_id', 'locale'])]
#[Hidden([
    'password',
    'two_factor_secret',
    'two_factor_recovery_codes',
    'remember_token',
    'qr_token',
])]
class User extends Authenticatable implements FilamentUser, HasLocalePreference
{
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * Only university staff may enter the Filament administration panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isUniversityStaff();
    }

    /**
     * The locale notifications (and other localized messages) are rendered in.
     *
     * Arabic is the platform default, so users who never explicitly switched
     * to English always receive Arabic regardless of who triggers the message
     * or the request locale at send time.
     */
    public function preferredLocale(): string
    {
        return $this->locale ?: 'ar';
    }

    /**
     * @return BelongsTo<University, $this>
     */
    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'two_factor_confirmed_at' => 'datetime',
        'role' => UserRole::class,
    ];

    public function eventAttendances()
    {
        return $this->hasMany(EventAttendance::class);
    }

    public function volunteerHours()
    {
        return $this->hasMany(VolunteerHour::class);
    }

    /**
     * The opaque token encoded in the user's personal attendance QR code,
     * generated and persisted lazily on first use.
     */
    public function attendanceQrToken(): string
    {
        if (empty($this->qr_token)) {
            $this->forceFill(['qr_token' => (string) Str::uuid()])->save();
        }

        return $this->qr_token;
    }

    /**
     * The user's attendance QR rendered as an inline SVG markup string. A club
     * Attendance Scanner reads the encoded token to log the student's presence.
     */
    public function attendanceQrSvg(): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(320, 1),
            new SvgImageBackEnd,
        );

        return (new Writer($renderer))->writeString($this->attendanceQrToken());
    }

    /**
     * @return HasMany<Certificate, $this>
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function clubMemberships()
    {
        return $this->hasMany(ClubMembership::class);
    }

    public function clubMembership()
    {
        return $this->hasOne(ClubMembership::class);
    }

    public function club()
    {
        return $this->hasOneThrough(
            Club::class,
            ClubMembership::class,
            'user_id', // FK in club_memberships
            'id',      // PK in clubs
            'id',      // PK in users
            'club_id'  // FK in club_memberships
        );
    }

    public function joinApplications()
    {
        return $this->hasMany(ClubJoinApplication::class);
    }

    /**
     * Whether the user belongs to the genuinely-global university-staff tier.
     */
    public function isUniversityStaff(): bool
    {
        return $this->role === UserRole::UniversityStaff;
    }

    /**
     * Whether the user belongs to the student tier.
     */
    public function isStudent(): bool
    {
        return $this->role === UserRole::Student;
    }

    /**
     * The (relative) URL the user should land on after authenticating. Staff go
     * to the Filament panel; users who manage one or more clubs land on their
     * first club's management dashboard; everyone else goes to the student
     * dashboard.
     */
    public function homeUrl(): string
    {
        if ($this->isUniversityStaff()) {
            return route(UserRole::UniversityStaff->dashboardRoute(), absolute: false);
        }

        $club = $this->managedClubs()->first();

        if ($club !== null) {
            return route('clubs.manage', $club, absolute: false);
        }

        return route(UserRole::Student->dashboardRoute(), absolute: false);
    }

    /**
     * The user's approved membership in a given club, if any.
     */
    public function clubMembershipFor(Club $club): ?ClubMembership
    {
        return $this->clubMemberships()
            ->where('club_id', $club->id)
            ->where('status', 'approved')
            ->with('roles')
            ->first();
    }

    /**
     * The capabilities the user holds within a given club (union of role capabilities).
     *
     * @return Collection<int, ClubCapability>
     */
    public function clubCapabilitiesFor(Club $club): Collection
    {
        $membership = $this->clubMembershipFor($club);

        if ($membership === null) {
            return collect();
        }

        return $membership->clubRoles()
            ->flatMap(fn (ClubRole $role): array => $role->capabilities())
            ->unique()
            ->values();
    }

    public function hasClubCapability(ClubCapability $capability, Club $club): bool
    {
        return $this->clubCapabilitiesFor($club)->contains($capability);
    }

    /**
     * Whether the user may open a club's management dashboard. University staff
     * may manage any club (Gate::before); everyone else needs at least one
     * club-scoped capability there.
     */
    public function canManageClub(Club $club): bool
    {
        return $this->isUniversityStaff() || $this->clubCapabilitiesFor($club)->isNotEmpty();
    }

    /**
     * The clubs in which the user holds at least one management role, with the
     * club's branding eager-loaded. Soft-deleted (archived) clubs are excluded.
     *
     * @return Collection<int, Club>
     */
    public function managedClubs(): Collection
    {
        return $this->clubMemberships()
            ->where('status', 'approved')
            ->whereHas('roles', fn ($query) => $query->whereIn('role', ClubRole::managerRoleValues()))
            ->with('club')
            ->get()
            ->pluck('club')
            ->filter()
            ->unique('id')
            ->values();
    }

    /**
     * The first club in which the user holds a management role, if any.
     */
    public function managedClub(): ?Club
    {
        return $this->managedClubs()->first();
    }

    /**
     * @return HasMany<CommitteeMembership, $this>
     */
    public function committeeMemberships()
    {
        return $this->hasMany(CommitteeMembership::class);
    }

    /**
     * The user's approved membership in a given committee, if any.
     */
    public function committeeMembershipFor(Committee $committee): ?CommitteeMembership
    {
        return $this->committeeMemberships()
            ->where('committee_id', $committee->id)
            ->where('status', 'approved')
            ->with('roles')
            ->first();
    }

    /**
     * The capabilities the user holds within a given committee. University staff
     * and the parent club's leads (holders of ManageClub) implicitly hold every
     * committee capability — they oversee all of a club's committees. Everyone
     * else gets the union of their committee-role capabilities.
     *
     * @return Collection<int, CommitteeCapability>
     */
    public function committeeCapabilitiesFor(Committee $committee): Collection
    {
        if ($this->isUniversityStaff() || $this->hasClubCapability(ClubCapability::ManageClub, $committee->club)) {
            return collect(CommitteeCapability::all());
        }

        $membership = $this->committeeMembershipFor($committee);

        if ($membership === null) {
            return collect();
        }

        return $membership->committeeRoles()
            ->flatMap(fn (CommitteeRole $role): array => $role->capabilities())
            ->unique()
            ->values();
    }

    public function hasCommitteeCapability(CommitteeCapability $capability, Committee $committee): bool
    {
        return $this->committeeCapabilitiesFor($committee)->contains($capability);
    }

    /**
     * Whether the user may open a committee's management dashboard.
     */
    public function canManageCommittee(Committee $committee): bool
    {
        return $this->committeeCapabilitiesFor($committee)->isNotEmpty();
    }

    /**
     * The committees in which the user holds at least one management role.
     * Soft-deleted (archived) committees and those of archived clubs are excluded.
     *
     * @return Collection<int, Committee>
     */
    public function managedCommittees(): Collection
    {
        return $this->committeeMemberships()
            ->where('status', 'approved')
            ->whereHas('roles', fn ($query) => $query->whereIn('role', CommitteeRole::managerRoleValues()))
            ->whereHas('committee.club')
            ->with('committee')
            ->get()
            ->pluck('committee')
            ->filter()
            ->unique('id')
            ->values();
    }
}
