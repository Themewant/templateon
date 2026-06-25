(function ($) {
    'use strict';

    const Config = window.TEMPLATEON || {};
    const i18n = Config.i18n || {};

    class TemplateonApp {
        constructor() {
            this.BATCH_SIZE = 12;

            this.state = {
                categories: [],
                type: 'all',
                search: '',
                favoritesOnly: false,
                currentTemplate: null,
                visibleLimit: this.BATCH_SIZE,
                hasMore: false,
            };

            this.$app     = $('#templa-app');
            this.$grid    = $('#templa-grid');
            this.$cards   = this.$grid.find('.templa-card');
            this.$empty   = $('#templa-empty');
            this.$toast   = $('#templa-toast');

            this.$sentinel = $('#templa-infinite-sentinel');
            this.$infiniteLoader = $('#templa-infinite-loader');

            this.$import      = $('#templa-import-modal');

            this.bind();
            this.setupInfiniteScroll();
            this.setupThumbScroll();
            this.applyFilters();
        }

        setupThumbScroll() {
            const measure = (img) => {
                const thumb = img.parentElement;
                if (!thumb) return;
                const thumbH = thumb.clientHeight;
                const imgH = img.offsetHeight;
                const delta = thumbH - imgH;
                if (delta < -20) {
                    img.classList.add('is-scrollable');
                    img.style.setProperty('--templa-scroll-y', delta + 'px');
                } else {
                    img.classList.remove('is-scrollable');
                    img.style.removeProperty('--templa-scroll-y');
                }
            };

            this.$grid.find('.templa-card-thumb img').each((_i, img) => {
                if (img.complete && img.naturalWidth) measure(img);
                else img.addEventListener('load', () => measure(img), { once: true });
            });

            let resizeTimer;
            $(window).on('resize', () => {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(() => {
                    this.$grid.find('.templa-card-thumb img').each((_i, img) => measure(img));
                }, 150);
            });
        }

        bind() {
            // ---------- Filters ----------
            $('#templa-search-input').on('input', (e) => {
                this.state.search = e.target.value.trim().toLowerCase();
                this.applyFilters();
            });

            $('#templa-type-filter').on('change', (e) => {
                this.state.type = e.target.value;
                this.applyFilters();
            });

            // ---------- Category mega menu ----------
            const $catReset = $('#templa-cat-reset');

            // Keep dropdown panels on-screen: flip to right-aligned only when the
            // left-aligned panel would overflow the viewport's right edge.
            this.$app.on('mouseenter focusin', '.templa-mega-item.has-children', (e) => {
                const $item = $(e.currentTarget);
                const panel = $item.children('.templa-mega-panel')[0];
                if (!panel) return;
                $item.removeClass('templa-panel-flip');
                const itemLeft = $item[0].getBoundingClientRect().left;
                const panelWidth = panel.getBoundingClientRect().width;
                if (itemLeft + panelWidth > window.innerWidth - 16) {
                    $item.addClass('templa-panel-flip');
                }
            });

            this.$app.on('click', '.templa-mega-item.has-children > .templa-mega-trigger', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const $item = $(e.currentTarget).closest('.templa-mega-item');

                const parentSlug = $item.attr('data-parent');
                const $children = $item.find('.templa-mega-child.templa-cat-item');
                const childSlugs = $children.map((_i, el) => $(el).attr('data-cat')).get();
                const bucket = parentSlug ? [parentSlug].concat(childSlugs) : childSlugs;
                if (!bucket.length) return;

                const anySelected = (parentSlug && this.state.categories.indexOf(parentSlug) !== -1)
                    || $children.filter('.is-active').length > 0;

                this.state.categories = this.state.categories.filter((c) => bucket.indexOf(c) === -1);

                if (anySelected) {
                    $children.removeClass('is-active').attr('aria-checked', 'false');
                    $item.removeClass('has-selected');
                } else {
                    bucket.forEach((slug) => this.state.categories.push(slug));
                    $children.addClass('is-active').attr('aria-checked', 'true');
                }

                this.updateMegaState();
                this.applyFilters();
            });

            // Click a child item → multi-select toggle. Leaf parents (no children) act as a single chip toggle.
            this.$app.on('click', '.templa-cat-item', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const $item = $(e.currentTarget);
                const cat = $item.attr('data-cat');
                if (!cat) return;

                const isLeaf = $item.hasClass('templa-mega-leaf');
                const idx = this.state.categories.indexOf(cat);
                const wasActive = idx !== -1;

                if (isLeaf) {
                   
                    $('.templa-cat-item.is-active').removeClass('is-active').attr('aria-checked', 'false');
                    if (wasActive) {
                        this.state.categories = [];
                    } else {
                        this.state.categories = [cat];
                        $(`.templa-cat-item[data-cat="${cat}"]`)
                            .addClass('is-active')
                            .attr('aria-checked', 'true');
                    }
                } else {
                    // Child rows: additive multi-select toggle.
                    if (wasActive) {
                        this.state.categories.splice(idx, 1);
                        $(`.templa-cat-item[data-cat="${cat}"]`)
                            .removeClass('is-active')
                            .attr('aria-checked', 'false');
                    } else {
                        this.state.categories.push(cat);
                        $(`.templa-cat-item[data-cat="${cat}"]`)
                            .addClass('is-active')
                            .attr('aria-checked', 'true');
                    }
                }

                this.updateMegaState();
                this.applyFilters();
            });

           
            this.$app.on('click', '.templa-mega-deselect-all', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const parent = $(e.currentTarget).attr('data-parent');
                if (!parent) return;

                const slugs = $(`.templa-cat-item[data-parent="${parent}"]`)
                    .map((_i, el) => $(el).attr('data-cat')).get();

                this.state.categories = this.state.categories.filter((c) => slugs.indexOf(c) === -1);

                $(`.templa-cat-item[data-parent="${parent}"]`)
                    .removeClass('is-active')
                    .attr('aria-checked', 'false');

                this.updateMegaState();
                this.applyFilters();
            });

            $catReset.on('click', (e) => {
                e.stopPropagation();
                $('.templa-cat-item.is-active')
                    .removeClass('is-active')
                    .attr('aria-checked', 'false');
                this.state.categories = [];
                this.updateMegaState();
                this.applyFilters();
            });

            this.updateMegaState();

            $('#templa-favorites-toggle').on('click', (e) => {
                const $btn = $(e.currentTarget);
                this.state.favoritesOnly = !this.state.favoritesOnly;
                $btn.toggleClass('is-active', this.state.favoritesOnly);
                $btn.attr('aria-pressed', this.state.favoritesOnly ? 'true' : 'false');
                this.applyFilters();
            });

            // ---------- Sync ----------
            $('#templa-sync-btn').on('click', () => this.syncLibrary());

            // ---------- Favourite toggle ----------
            this.$app.on('click', '.templa-fav-btn', (e) => {
                e.stopPropagation();
                const $btn = $(e.currentTarget);
                this.toggleFavorite($btn.data('slug'), $btn);
            });

            // ---------- Preview ----------
            // Preview is a plain link that opens the live demo in a new browser

            // ---------- Import ----------
            this.$app.on('click', '.templa-import-btn', (e) => {
                e.preventDefault();
                const slug = $(e.currentTarget).data('slug');
                this.openImport(slug);
            });
            this.$import.on('click', '[data-close-import]', () => this.closeImport());
            $('#templa-start-import').on('click', () => this.startImport());

            // ---------- Per-plugin install / activate ----------
            this.$import.on('click', '.templa-plugin-action', (e) => {
                e.preventDefault();
                this.handlePluginAction($(e.currentTarget));
            });

            // ---------- Theme install / activate ----------
            this.$import.on('click', '.templa-theme-action', (e) => {
                e.preventDefault();
                this.handleThemeAction($(e.currentTarget));
            });

            // Escape closes the import modal
            $(document).on('keyup', (e) => {
                if (e.key === 'Escape' && !this.$import.attr('hidden')) {
                    this.closeImport();
                }
            });
        }

        // ==================================================
        // Filters
        // ==================================================
        applyFilters({ resetVisible = true } = {}) {
            if (resetVisible) this.state.visibleLimit = this.BATCH_SIZE;

            const { categories, type, search, favoritesOnly, visibleLimit } = this.state;
            let matched = 0;

            this.$cards.each((_i, el) => {
                const $c = $(el);
                const cats = ($c.attr('data-categories') || '').split(' ').filter(Boolean);
                const name = ($c.attr('data-name') || '');
                const cardType = $c.attr('data-type') === 'pro' ? 'pro' : 'free';
                const isFav = $c.attr('data-favorite') === '1';

                let passes = true;
                if (categories.length && !categories.some((c) => cats.includes(c))) passes = false;
                if (type !== 'all' && cardType !== type) passes = false;
                if (search && name.indexOf(search) === -1) passes = false;
                if (favoritesOnly && !isFav) passes = false;

                if (!passes) {
                    $c.addClass('is-hidden');
                    return;
                }

                matched++;
                $c.toggleClass('is-hidden', matched > visibleLimit);
            });

            this.$empty.attr('hidden', matched > 0 ? true : false);

            this.state.hasMore = matched > this.state.visibleLimit;
            this.$infiniteLoader.attr('hidden', !this.state.hasMore);

            if (this.state.hasMore && this.scrollObserver && this.$sentinel[0]) {
                this.scrollObserver.unobserve(this.$sentinel[0]);
                this.scrollObserver.observe(this.$sentinel[0]);
            }
        }

        updateMegaState() {
            const count = this.state.categories.length;

            $('.templa-mega-item').each((_i, el) => {
                const $item = $(el);
                const activeCount = $item.find('.templa-cat-item.is-active').length;
                $item.toggleClass('has-selected', activeCount > 0);

                const $deselect = $item.find('.templa-mega-deselect-all');
                if ($deselect.length) {
                    if (activeCount > 0) $deselect.removeAttr('hidden');
                    else $deselect.attr('hidden', true);
                }
            });

            const $reset = $('#templa-cat-reset');
            const $resetCount = $('#templa-mega-reset-count');
            if (count > 0) {
                $reset.removeAttr('hidden');
                $resetCount.text(count);
            } else {
                $reset.attr('hidden', true);
            }
        }

        setupInfiniteScroll() {
            const sentinel = this.$sentinel[0];
            if (!sentinel || typeof IntersectionObserver === 'undefined') return;

            this.scrollObserver = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return;
                    if (!this.state.hasMore) return;
                    this.state.visibleLimit += this.BATCH_SIZE;
                    this.applyFilters({ resetVisible: false });
                });
            }, { rootMargin: '300px 0px' });

            this.scrollObserver.observe(sentinel);
        }

        // ==================================================
        // Favorites
        // ==================================================
        toggleFavorite(slug, $btn) {
            $.post(Config.ajaxUrl, {
                action: 'templateon_toggle_favorite',
                nonce: Config.nonce,
                slug: slug,
            }).done((res) => {
                if (!res.success) return;
                const added = res.data.state === 'added';
                $btn.toggleClass('is-active', added);
                $btn.attr('aria-pressed', added ? 'true' : 'false');
                $btn.closest('.templa-card').attr('data-favorite', added ? '1' : '0');
                if (this.state.favoritesOnly) this.applyFilters();
            });
        }

        // ==================================================
        // Sync library
        // ==================================================
        syncLibrary() {
            const $btn = $('#templa-sync-btn');
            $btn.addClass('is-spinning');

            $.post(Config.ajaxUrl, {
                action: 'templateon_sync_library',
                nonce: Config.nonce,
            }).done((res) => {
                this.toast(res.success ? i18n.synced : i18n.syncFailed, res.success ? 'success' : 'error');
                if (res.success) setTimeout(() => window.location.reload(), 800);
            }).fail(() => this.toast(i18n.syncFailed, 'error'))
              .always(() => $btn.removeClass('is-spinning'));
        }

        // ==================================================
        // Import wizard
        // ==================================================
        openImport(slug) {
            this.state.currentTemplate = slug;
            this.showStep('requirements');
            this.$import.removeAttr('hidden');
            document.body.style.overflow = 'hidden';
        }
        closeImport() {
            this.$import.attr('hidden', true);
            document.body.style.overflow = '';
        }
        showStep(step) {
            this.$import.find('.templa-import-step').removeClass('is-active');
            this.$import.find(`.templa-import-step[data-step="${step}"]`).addClass('is-active');
        }

        async startImport() {
            const slug = this.state.currentTemplate;
            if (!slug) return;

            this.showStep('progress');
            this.setProgress(5, 'Checking requirements…');

            try {
                // 1. Plugins
                await this.processPlugins();

                // 2. Theme
                this.setProgress(45, 'Setting up theme…');
                await this.processTheme();

                // 3. Content
                this.setProgress(60, 'Importing content…');
                await this.importContent(slug);

                this.setProgress(100, 'Completed!');
                setTimeout(() => this.showStep('done'), 500);
            } catch (err) {
                console.error(err);
                this.toast((err && err.message) || i18n.importError, 'error');
                this.showStep('requirements');
            }
        }

        async processPlugins() {
            const $rows = this.$import.find('.templa-plugin-row');
            const total = $rows.length || 1;
            const stepSize = 35 / total;
            let done = 0;

            for (const row of $rows.toArray()) {
                const $r = $(row);
                const slug = $r.data('slug');
                const init = $r.attr('data-init') || '';
                const isActive = $r.data('active') === true || $r.data('active') === 'true';
                const isInstalled = $r.data('installed') === true || $r.data('installed') === 'true';

                if (!isActive) {
                    if (!isInstalled) {
                        this.setProgress(8 + done * stepSize, `Installing ${slug}…`);
                        await this.ajax('templateon_install_plugin', { slug });
                    }
                    this.setProgress(8 + done * stepSize + stepSize / 2, `Activating ${slug}…`);
                    await this.ajax('templateon_activate_plugin', { slug, init });
                    this.markPluginActive($r);
                }
                done++;
            }
        }

        async handlePluginAction($btn) {
            if ($btn.hasClass('is-loading')) return;

            const $row = $btn.closest('.templa-plugin-row');
            const slug = $row.data('slug');
            const init = $row.attr('data-init') || '';
            const mode = $btn.data('do');
            const $label = $btn.find('.templa-btn-mini-label');
            const originalLabel = $label.text();

            $btn.addClass('is-loading').prop('disabled', true);
            $label.text(mode === 'install' ? (i18n.installing || 'Installing…') : (i18n.activating || 'Activating…'));

            try {
                if (mode === 'install') {
                    await this.ajax('templateon_install_plugin', { slug });
                    $row.attr('data-installed', 'true').data('installed', 'true');
                    await this.ajax('templateon_activate_plugin', { slug, init });
                } else {
                    await this.ajax('templateon_activate_plugin', { slug, init });
                }

                this.markPluginActive($row);
                this.toast(i18n.pluginReady || 'Plugin ready.', 'success');
            } catch (err) {
                console.error(err);
                $btn.removeClass('is-loading').prop('disabled', false);
                $label.text(originalLabel);
                this.toast((err && err.message) || i18n.importError || 'Action failed', 'error');
            }
        }

        markPluginActive($row) {
            $row.attr('data-installed', 'true').data('installed', 'true');
            $row.attr('data-active', 'true').data('active', 'true');

            $row.find('.templa-status')
                .removeClass('templa-status--missing templa-status--inactive')
                .addClass('templa-status--active')
                .text('Active');

            $row.find('.templa-plugin-action').remove();
        }

        async handleThemeAction($btn) {
            if ($btn.hasClass('is-loading')) return;

            const $row = $btn.closest('.templa-theme-row');
            const slug = $row.data('slug');
            const mode = $btn.data('do');
            const $label = $btn.find('.templa-btn-mini-label');
            const originalLabel = $label.text();

            $btn.addClass('is-loading').prop('disabled', true);
            $label.text(mode === 'install' ? (i18n.installing || 'Installing…') : (i18n.activating || 'Activating…'));

            try {
                if (mode === 'install') {
                    await this.ajax('templateon_install_theme', { slug });
                    $row.attr('data-installed', 'true').data('installed', 'true');
                    await this.ajax('templateon_activate_theme', { slug });
                } else {
                    await this.ajax('templateon_activate_theme', { slug });
                }

                this.markThemeActive($row);
                this.toast(i18n.themeReady || 'Theme activated.', 'success');
            } catch (err) {
                console.error(err);
                $btn.removeClass('is-loading').prop('disabled', false);
                $label.text(originalLabel);
                this.toast((err && err.message) || i18n.importError || 'Action failed', 'error');
            }
        }

        markThemeActive($row) {
            $row.attr('data-installed', 'true').data('installed', 'true');
            $row.attr('data-active', 'true').data('active', 'true');

            $row.find('.templa-status')
                .removeClass('templa-status--missing templa-status--inactive')
                .addClass('templa-status--active')
                .text('Active');

            const $check = $row.find('#templa-theme-check');
            if ($check.length) {
                $check.prop('checked', true).attr('data-installed', 'true').data('installed', 'true');
            }

            $row.find('.templa-theme-action').remove();
        }

        async processTheme() {
            const $check = $('#templa-theme-check');
            if (!$check.length || !$check.is(':checked')) return;

            const slug = $check.val();
            const installed = $check.data('installed') === true || $check.data('installed') === 'true';

            if (!installed) {
                this.setProgress(48, `Installing theme ${slug}…`);
                await this.ajax('templateon_install_theme', { slug });
            }
            this.setProgress(52, `Activating theme ${slug}…`);
            await this.ajax('templateon_activate_theme', { slug });

            const $row = this.$import.find('.templa-theme-row');
            if ($row.length) this.markThemeActive($row);
        }

        importContent(slug) {
            return this.ajax('templateon_import_content', { template: slug });
        }

        setProgress(percent, label) {
            const pct = Math.min(100, Math.max(0, percent | 0));
            $('#templa-progress-fill').css('width', pct + '%');
            $('#templa-progress-text').text(pct + '% — ' + (label || ''));
        }

        // ==================================================
        // Helpers
        // ==================================================
        ajax(action, data = {}) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: Config.ajaxUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: Object.assign({ action, nonce: Config.nonce }, data),
                }).done((res) => {
                    if (res && res.success) return resolve(res);
                    const msg = (res && res.data && res.data.message) || 'Unknown error';
                    if (/already installed|Destination folder already exists/i.test(msg)) {
                        return resolve(res);
                    }
                    reject(new Error(msg));
                }).fail((xhr) => {
                    reject(new Error('Network error: ' + xhr.statusText));
                });
            });
        }

        toast(message, variant) {
            this.$toast
                .text(message)
                .removeClass('is-error is-success')
                .addClass(variant === 'error' ? 'is-error' : (variant === 'success' ? 'is-success' : ''))
                .removeAttr('hidden');
            clearTimeout(this._toastTimer);
            this._toastTimer = setTimeout(() => this.$toast.attr('hidden', true), 2800);
        }
    }

    $(function () { new TemplateonApp(); });
})(jQuery);
