import { afterEach, vi, beforeEach } from "vitest";

// Mock Inertia route function globally
(globalThis as Record<string, unknown>).route = vi.fn(
    (name: string, params?: Record<string, unknown>) => {
        if (params) {
            return `/${name}/${params}`;
        }
        return `/${name}`;
    },
);

// Mock fetch globally
(globalThis as Record<string, unknown>).fetch = vi.fn(() =>
    Promise.resolve({
        ok: true,
        json: () => Promise.resolve({ data: [] }),
    } as Response),
);

// Mock localStorage
beforeEach(() => {
    Storage.prototype.getItem = vi.fn(() => null);
    Storage.prototype.setItem = vi.fn();
    Storage.prototype.removeItem = vi.fn();
});

afterEach(() => {
    vi.clearAllMocks();
});
