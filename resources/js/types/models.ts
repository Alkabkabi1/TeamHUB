/**
 * Shared domain models used across multiple pages.
 *
 * These were previously redefined inline in each page; centralizing them keeps
 * the shapes consistent and gives a single place to evolve the backend contract.
 *
 * The `Club` shapes form a hierarchy from the smallest reference to the full
 * list item, so a page can import the narrowest type that matches its props.
 */

/** Minimal club reference — just enough to link to or label a club. */
export type ClubRef = {
    id: number;
    name: string;
};

/** Club reference plus branding fields (used by theme/branding screens). */
export type ClubBranding = ClubRef & {
    theme: string | null;
    /** Public URL for the club logo (from the media library), or null. */
    logo_url: string | null;
};

/** A club with its catalog metadata (category/college/status). */
export type Club = ClubBranding & {
    category: string | null;
    college: string | null;
    status: string;
};

/** A tag attached to a club (catalog taxonomy). */
export type TagSummary = {
    id: number;
    name: string;
};

/** A club as shown in listing grids, including its member count and tags. */
export type ClubListItem = Club & {
    members_count: number;
    tags?: TagSummary[];
    is_member?: boolean;
};

/** A selectable option with a machine value and a localized label. */
export type SelectOption = {
    value: string;
    label: string;
};

/** @deprecated Use {@link SelectOption}. */
export type StatusOption = SelectOption;

/**
 * A single image stored via the media library. `id` lets edit forms remove a
 * specific existing image; `url` is the public URL to render.
 */
export type MediaImage = {
    id: number;
    url: string;
    name?: string;
};

/** Compact event shape used on the landing page and other summaries. */
export type EventSummary = {
    id: number;
    title: string;
    description: string | null;
    starts_at: string;
    club: ClubRef;
    /** Cover image URL (first gallery image), or null. */
    image_url: string | null;
};

/** Full event shape used in the events catalog/listing. */
export type CatalogEvent = {
    id: number;
    title: string;
    description: string | null;
    starts_at: string;
    ends_at: string;
    location: string | null;
    capacity: number | null;
    status: string;
    registrations_count: number;
    /** Cover image URL (first gallery image), or null. */
    image_url: string | null;
    club: ClubRef & {
        category: string | null;
        college: string | null;
    };
};

/** Single event shape used on the event detail page. */
export type EventDetail = CatalogEvent & {
    is_full: boolean;
    is_open: boolean;
    /** All gallery image URLs, in display order. */
    images: string[];
};
