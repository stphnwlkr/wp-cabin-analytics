/**
 * WP Cabin Analytics - Popover + Anchor positioning (multi-instance)
 */
(function () {
  function supportsPopover() {
    return typeof HTMLElement !== "undefined" && "popover" in HTMLElement.prototype;
  }
  function supportsAnchors() {
    return typeof CSS !== "undefined" && CSS.supports && CSS.supports("anchor-name: --a");
  }

  function hideAll(root) {
    root.querySelectorAll("[data-wp-cabin-popover][popover]").forEach(function (p) {
      if (p && p.hidePopover) { try { p.hidePopover(); } catch (e) {} }
    });
  }

  function bind(root) {
    var hits = root.querySelectorAll(".wp-cabin-hit[data-wp-cabin-anchor]");
    if (!hits.length) return;

    document.addEventListener("click", function (e) {
      if (!root.contains(e.target)) hideAll(root);
    });

    hits.forEach(function (btn) {
      btn.addEventListener("click", function () {
        if (!supportsPopover() || !supportsAnchors()) return;

        var popId = btn.getAttribute("data-wp-cabin-popover-id");
        var pop = popId ? root.querySelector("#" + CSS.escape(popId)) : root.querySelector("[data-wp-cabin-popover]");
        if (!pop) return;

        var titleEl = pop.querySelector("[data-wp-cabin-pop-title]");
        var uniqEl  = pop.querySelector("[data-wp-cabin-pop-uniq]");
        var viewsEl = pop.querySelector("[data-wp-cabin-pop-views]");

        if (titleEl) titleEl.textContent = btn.getAttribute("data-label") || "";
        if (uniqEl)  uniqEl.textContent  = "Visitors: " + (btn.getAttribute("data-uniq") || "—");
        if (viewsEl) viewsEl.textContent = "Views: " + (btn.getAttribute("data-views") || "—");

        var anchor = btn.getAttribute("data-wp-cabin-anchor");
        if (anchor) pop.style.setProperty("--wp-cabin-active-anchor", anchor);

        try {
          hideAll(root);
          pop.showPopover();
        } catch (e) {}
      });
    });

    root.addEventListener("keydown", function (e) {
      if (e.key === "Escape") hideAll(root);
    });
  }

  function initAll() {
    document.querySelectorAll("[data-wp-cabin-instance]").forEach(bind);
  }

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", initAll);
  else initAll();
})();
