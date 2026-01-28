const initCustomSelects = () => {
    document.querySelectorAll("[data-select]").forEach((wrap) => {
        const select = wrap.querySelector("select");
        const trigger = wrap.querySelector("[data-select-trigger]");
        const label = wrap.querySelector("[data-select-label]");
        const menu = wrap.querySelector("[data-select-menu]");
        const options = Array.from(wrap.querySelectorAll("[data-select-option]"));

        if (!select || !trigger || !label || !menu) {
            return;
        }

        const sync = () => {
            const selected = select.options[select.selectedIndex];
            label.textContent = selected ? selected.textContent : "";
            options.forEach((option) => {
                const isActive = option.getAttribute("data-value") === select.value;
                option.classList.toggle("is-active", isActive);
                option.setAttribute("aria-selected", isActive ? "true" : "false");
            });
        };

        const closeMenu = () => {
            wrap.classList.remove("is-open");
            trigger.setAttribute("aria-expanded", "false");
        };

        sync();

        trigger.addEventListener("click", () => {
            const willOpen = !wrap.classList.contains("is-open");
            wrap.classList.toggle("is-open", willOpen);
            trigger.setAttribute("aria-expanded", willOpen ? "true" : "false");
            if (willOpen) {
                menu.focus();
            }
        });

        options.forEach((option) => {
            option.addEventListener("click", () => {
                const value = option.getAttribute("data-value");
                if (value !== null) {
                    select.value = value;
                    sync();
                    select.dispatchEvent(new Event("change", { bubbles: true }));
                }
                closeMenu();
            });
        });

        document.addEventListener("click", (event) => {
            if (!wrap.contains(event.target)) {
                closeMenu();
            }
        });

        document.addEventListener("keydown", (event) => {
            if (!wrap.classList.contains("is-open")) {
                return;
            }
            if (event.key === "Escape") {
                closeMenu();
                trigger.focus();
            }
        });
    });
};

const initCategoryModal = () => {
    const addButton = document.querySelector(".category-panel__add-btn");
    const modal = document.querySelector("[data-category-modal]");

    if (!addButton || !modal) {
        return;
    }

    const closeButtons = Array.from(
        modal.querySelectorAll("[data-category-modal-close]")
    );
    const focusTarget = modal.querySelector("[data-category-modal-focus]");

    const openModal = () => {
        modal.classList.add("is-open");
        modal.setAttribute("aria-hidden", "false");
        document.body.classList.add("is-modal-open");
        if (focusTarget) {
            setTimeout(() => {
                focusTarget.focus();
            }, 50);
        }
    };

    const closeModal = () => {
        modal.classList.remove("is-open");
        modal.setAttribute("aria-hidden", "true");
        document.body.classList.remove("is-modal-open");
        addButton.focus();
    };

    addButton.addEventListener("click", openModal);
    closeButtons.forEach((button) => {
        button.addEventListener("click", closeModal);
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && modal.classList.contains("is-open")) {
            closeModal();
        }
    });
};

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => {
        initCustomSelects();
        initCategoryModal();
    });
} else {
    initCustomSelects();
    initCategoryModal();
}
