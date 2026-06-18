import DOMPurify from 'dompurify';
import { marked, Renderer } from 'marked';

marked.setOptions({ gfm: true, breaks: true });

const renderer = new Renderer();

renderer.link = ({ href, title, text }) => {
    if (!href) {
        return `<a>${text}</a>`;
    }

    const titleAttr = title ? ` title="${DOMPurify.sanitize(title)}"` : '';

    const isInternal =
        href.startsWith('/') ||
        (typeof window !== 'undefined' &&
            href.startsWith(window.location.origin));

    if (isInternal) {
        return `<a href="${href}"${titleAttr} data-inertia-link="true">${text}</a>`;
    }

    return `<a href="${href}"${titleAttr} target="_blank" rel="noopener noreferrer">${text}</a>`;
};

marked.use({ renderer });

/**
 * Render untrusted markdown (LLM output, which may embed user-generated tool
 * data) to sanitized HTML safe for `{@html}`. Runs on the client only — the
 * assistant's messages never exist during SSR.
 */
export function renderMarkdown(text: string): string {
    const html = marked.parse(text ?? '', { async: false });

    return DOMPurify.sanitize(html, {
        ADD_ATTR: ['target', 'rel', 'data-inertia-link'],
    });
}
