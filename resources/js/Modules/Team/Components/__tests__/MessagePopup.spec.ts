import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount } from "@vue/test-utils";
import MessagePopup from "../MessagePopup.vue";

// Mock fetch
(globalThis as any).fetch = vi.fn(() =>
    Promise.resolve({
        ok: true,
        json: () => Promise.resolve({ messages: [] }),
    } as Response),
);

describe("MessagePopup", () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it("renders when open", () => {
        const wrapper = mount(MessagePopup, {
            props: {
                isOpen: true,
                recipient: undefined,
            },
        });

        expect(wrapper.find(".fixed").exists()).toBe(true);
    });

    it("does not render when closed", () => {
        const wrapper = mount(MessagePopup, {
            props: {
                isOpen: false,
                recipient: undefined,
            },
        });

        expect(wrapper.find(".fixed").exists()).toBe(false);
    });

    it("displays recipient name when provided", () => {
        const recipient = {
            id: 1,
            name: "Test User",
            email: "test@example.com",
        };

        const wrapper = mount(MessagePopup, {
            props: {
                isOpen: true,
                recipient,
            },
        });

        expect(wrapper.text()).toContain("Test User");
        expect(wrapper.text()).toContain("test@example.com");
    });

    it('displays "Messages" when no recipient', () => {
        const wrapper = mount(MessagePopup, {
            props: {
                isOpen: true,
                recipient: undefined,
            },
        });

        expect(wrapper.text()).toContain("Messages");
    });

    it("shows loading state", () => {
        const wrapper = mount(MessagePopup, {
            props: {
                isOpen: true,
                recipient: { id: 1, name: "Test", email: "test@example.com" },
            },
            data() {
                return {
                    loading: true,
                };
            },
        });

        expect(wrapper.text()).toContain("Loading messages...");
    });

    it("shows empty state when no messages", async () => {
        // Mock fetch to return empty messages immediately
        (globalThis as any).fetch = vi.fn(() =>
            Promise.resolve({
                ok: true,
                json: () => Promise.resolve({ messages: [] }),
            } as Response),
        );

        const wrapper = mount(MessagePopup, {
            props: {
                isOpen: true,
                recipient: { id: 1, name: "Test", email: "test@example.com" },
            },
            global: {
                stubs: {
                    Transition: {
                        template: '<div><slot /></div>',
                    },
                },
            },
        });

        // Wait for component to finish loading
        await new Promise(resolve => setTimeout(resolve, 100));
        await wrapper.vm.$nextTick();

        // Check for empty state text (may be split across elements)
        const text = wrapper.text()
        expect(text).toMatch(/No messages yet/i)
        expect(text).toMatch(/Start the conversation/i)
    });

    it("emits close event when close button clicked", async () => {
        const wrapper = mount(MessagePopup, {
            props: {
                isOpen: true,
                recipient: undefined,
            },
        });

        const closeButton = wrapper.find("button");
        await closeButton.trigger("click");

        expect(wrapper.emitted("close")).toBeTruthy();
    });
});
