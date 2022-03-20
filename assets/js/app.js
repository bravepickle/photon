/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

(function () {
    'use strict';

    // any CSS you require will output into a single css file (app.scss in this case)
    require('../css/app.scss');
    //
    // require('@fortawesome/fontawesome-free/css/fontawesome.css');
    // require('@fortawesome/fontawesome-free/css/solid.css');
    //
    // require('photoswipe/dist/photoswipe.css');
    // require('photoswipe/dist/default-skin/default-skin.css');

    let PhotoSwipe = require('photoswipe');
    let PhotoSwipeUI_Default = require('photoswipe/dist/photoswipe-ui-default');

    // Need jQuery? Install it with "yarn add jquery", then uncomment to require it.
    // const $ = require('jquery');

    // console.log('Hello Webpack Encore! Edit me in assets/js/app.js');


    let VisitsTracker = function (currentPath) {
        this.enabled = false;
        this.path = '';
        this.visitedClass = 'visited';

        let _self = this;

        this.init = function (currentPath) {
            this.path = currentPath;
            // this.enabled = enabled;
            this.enabled = sessionStorage.getItem('visitsTrackingEnabled') === '1';

            if (this.enabled) {
                this.markVisitedLinks();
                this.addEvents();
            }

            this.addBtnEvent();
        };

        this.addBtnEvent = function () {
            let visitsBtn;
            visitsBtn = document.getElementById('toggle_visits_tracking_btn');

            visitsBtn.addEventListener('click', function () {
                if (_self.enabled) {
                    _self.clear();
                } else {
                    sessionStorage.setItem('visitsTrackingEnabled', '1');
                    sessionStorage.setItem('visits', JSON.stringify({}));
                }

                location.reload();
            });
        };

        this.addEvents = function () {
            let foundEls;
            foundEls = document.querySelectorAll('#folders_list a');

            if (foundEls !== null) {
                foundEls.forEach(function (el) {
                    el.addEventListener('click', function () {
                        _self.pushVisit(decodeURIComponent(this.getAttribute('href')));
                    });
                });
            }

            let visitsBtn;
            visitsBtn = document.getElementById('toggle_visits_tracking_btn');

            if (_self.enabled && !visitsBtn.classList.contains('selected')) {
                visitsBtn.classList.add('selected');
                visitsBtn.innerText = 'Disable Visits';
            } else {
                visitsBtn.innerText = 'Enable Visits';
            }
        };

        this.getVisits = function () {
            let visits;
            visits = sessionStorage.getItem('visits');

            if (visits === null) {
                return {};
            }

            return JSON.parse(visits);
        };

        this.pushVisit = function (link) {
            let visits;
            visits = this.getVisits();

            if (typeof visits[this.path] === 'undefined') {
                visits[this.path] = [];
            }

            visits[this.path].push(link);
            sessionStorage.setItem('visits', JSON.stringify(visits));

            return this;
        };

        this.markVisitedLinks = function () {
            let visits;

            visits = this.getVisits();

            if (typeof visits[this.path] === 'undefined') {
                return false;
            }

            visits[this.path].forEach(function (link) {
                let foundEls;
                let query;
                // query = '#folders_list a[href="' + encodeURIComponent(link) + '"]';
                query = '#folders_list a[href="' + link + '"]';
                foundEls = document.querySelectorAll(query);

                if (foundEls !== null) {
                    foundEls.forEach(function (el) {
                        if (!el.classList.contains(_self.visitedClass)) {
                            el.classList.add(_self.visitedClass);
                        }
                    });
                }
            });

            return true;
        };

        this.clear = function () {
            sessionStorage.removeItem('visitsTrackingEnabled');
            sessionStorage.removeItem('visits');

            return this;
        };

        this.init(currentPath);
    };

    let fileEls;
    let fileListContainer = document.querySelector('#files_list .items_list');

    fileEls = fileListContainer.querySelectorAll('li');

    if (fileEls !== null && fileEls.length > 0) {
        fileEls.forEach(function (listEl) {
            let downEl, upEl;
            downEl = listEl.querySelector('.move-down');

            downEl.addEventListener('click', function () {
                let wrapper = this.parentElement;

                if (wrapper.nextElementSibling) {
                    wrapper.parentNode.insertBefore(wrapper.nextElementSibling, wrapper);
                } else {
                    if (wrapper.previousSibling) {
                        wrapper.querySelector('.move-up').classList.remove('hidden');
                    }

                    this.classList.add('hidden');
                }
            });

            upEl = listEl.querySelector('.move-up');

            upEl.addEventListener('click', function () {
                let wrapper = this.parentElement;

                if (wrapper.previousElementSibling) {
                    wrapper.parentNode.insertBefore(wrapper, wrapper.previousElementSibling);
                }
            });
        });
    }

    window.addEventListener('load', function () {
        let viewBtn = document.getElementById('view_images_btn');
        let galleryIndex = 0;

        if (viewBtn) {
            viewBtn.addEventListener('click', function () {
                let pswpElement = document.querySelectorAll('.pswp')[0];

                // define options (if needed)
                // see https://photoswipe.com/documentation/options.html
                let options = {
                    index: galleryIndex,

                    // go from last to first on swipe next. This option has no relation to arrows navigation. Arrows loop is turned on permanently. You can modify this behavior by making custom UI.
                    // loop: false,

                    maxSpreadZoom: 5, // Maximum zoom level when performing spread (zoom) gesture.

                    shareButtons: [
                        {id: 'download', label: 'Download image', url: '{{raw_image_url}}', download: true}
                    ],
                };

                let sortedFileEls;
                sortedFileEls = document.querySelectorAll('#files_list a');

                if (sortedFileEls !== null && sortedFileEls.length > 0) {
                    let sortedLinks = [];
                    sortedFileEls.forEach(function(el) {
                        sortedLinks.push(el.getAttribute('href'));
                    });

                    window.slides.sort(function(a, b) {
                        let idxA, idxB;
                        idxA = sortedLinks.indexOf(a.src);
                        idxB = sortedLinks.indexOf(b.src);

                        if (idxA < idxB) {
                            return -1;
                        }

                        if (idxA > idxB) {
                            return 1;
                        }

                        return 0;
                    });
                }

                // Initializes and opens PhotoSwipe
                let gallery = new PhotoSwipe(pswpElement, PhotoSwipeUI_Default, window.slides, options);

                gallery.listen('close', function() {
                    galleryIndex = gallery.getCurrentIndex();
                });

                gallery.init();
            });
        }

        let delBtn = document.getElementById('delete_btn');

        if (delBtn) {
            delBtn.addEventListener('click', function () {
                if (confirm('Are you sure you want to delete current folder with all contents?')) {
                    // console.log('forms', document.forms['del_form']);
                    document.forms['del_form'].submit();
                }
            });
        }

        let toggleBtns = document.querySelectorAll('.app-btn-toggle');

        toggleBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                let targetEl = document.getElementById(this.getAttribute('data-target'));
                targetEl.classList.toggle("hidden");

                if (this.innerText === 'Show') {
                    this.innerText = 'Hide';
                } else {
                    this.innerText = 'Show';
                }
            });
        });

        let themeBtn = document.getElementById('current-theme');
        let themeListEl = document.getElementById('themes');
        let themeSelected = themeListEl.querySelector('.selected');

        if (themeSelected !== null) {
            themeSelected.addEventListener('click', function (event) {
                event.preventDefault(); // never click this link
            });
        }

        themeBtn.addEventListener('click', function (event) {
            event.preventDefault();

            themeListEl.classList.toggle('hidden');
        });

        // themeBtn.addEventListener('click', function () {
        //     themeListEl.classList.toggle('hidden');
        // });

        let sortFilesBtn = document.getElementById('apply_sort_btn');
        let sortSelector = document.getElementById('sort_pattern_sel');
        let sortCustomInput = document.getElementById('sort_custom');
        let sortReverseInput = document.getElementById('sort_reverse_input');

        sortCustomInput.value = sortSelector.value;

        sortSelector.addEventListener('change', function () {
            sortCustomInput.value = sortSelector.value;
        });

        function getSortValues(value, format) {
            let regExp = new RegExp(format);
            let matched = regExp.exec(value);
            let values = [];

            if (matched.groups) {
                if (typeof matched.groups.num !== 'undefined') {
                    values.push(parseInt(matched.groups.num));
                }

                if (typeof matched.groups.alpha !== 'undefined') {
                    values.push(matched.groups.alpha);
                }

                if (typeof matched.groups.num2 !== 'undefined') {
                    values.push(parseInt(matched.groups.num2));
                }

                if (typeof matched.groups.alpha2 !== 'undefined') {
                    values.push(matched.groups.alpha2);
                }

                if (typeof matched.groups.ext !== 'undefined') {
                    values.push(matched.groups.ext);
                }
            } else {
                console.log('Failed to find any regex groups for', value, matched);

                for (let i = 0; i < matched.length; i++) {
                    values.push(matched[i]);
                }
            }

            return values;
        }

        /**
         * @param a {object}
         * @param b {object}
         * @param format {string}
         * @param reverse {boolean}
         * @returns {number|number}
         */
        function sortSlidesCallback(a, b, format, reverse) {
            let aWeight = getSortValues(a.title, format);
            let bWeight = getSortValues(b.title, format);

            if (aWeight.length >= bWeight.length) {
                for (let i = 0; i < aWeight.length; i++) {
                    if (typeof bWeight[i] === 'undefined') {
                        return !reverse ? 1 : -1;
                    }

                    if (aWeight[i] > bWeight[i]) {
                        return !reverse ? 1 : -1;
                    }

                    if (aWeight[i] < bWeight[i]) {
                        return !reverse ? -1 : 1;
                    }
                }
            } else {
                for (let i = 0; i < bWeight.length; i++) {
                    if (typeof aWeight[i] === 'undefined') {
                        return !reverse ? 1 : -1;
                    }

                    if (aWeight[i] > bWeight[i]) {
                        return !reverse ? 1 : -1;
                    }

                    if (aWeight[i] < bWeight[i]) {
                        return !reverse ? -1 : 1;
                    }
                }
            }

            return 0;
        }

        /**
         *
         * @param format {string}
         * @param currentSlides {Array}
         * @param reverse {boolean}
         * @returns {*[]}
         */
        function sortByFormat(format, currentSlides, reverse) {
            let sortedSlides = currentSlides.sort(function (a, b) {
                return sortSlidesCallback(a, b, format, reverse);
            });

            for (let i = 0; i < sortedSlides.length; i++) {
                let el = fileListContainer.querySelector('a[href="' + sortedSlides[i].src + '"]').closest('li');
                fileListContainer.insertBefore(el, null);
            }

            return sortedSlides;
        }

        sortFilesBtn.addEventListener('click', function () {
            window.slides = sortByFormat(sortCustomInput.value, window.slides, !!sortReverseInput.checked);
        });

        new VisitsTracker(window.currentPath);
    });
}());


