/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you require will output into a single css file (app.css in this case)
require('../css/app.css');

require('photoswipe/dist/photoswipe.css');
require('photoswipe/dist/default-skin/default-skin.css');

let PhotoSwipe = require('photoswipe');
let PhotoSwipeUI_Default = require('photoswipe/dist/photoswipe-ui-default');

// require([
//     'photoswipe',
//     'photoswipe/dist/photoswipe-ui-default'
// ], function( PhotoSwipe, PhotoSwipeUI_Default ) {
//
//     //  	var gallery = new PhotoSwipe( someElement, PhotoSwipeUI_Default ...
//     //  	gallery.init()
//     //  	...
//
// });

// Need jQuery? Install it with "yarn add jquery", then uncomment to require it.
// const $ = require('jquery');

// console.log('Hello Webpack Encore! Edit me in assets/js/app.js');


let pswpElement = document.querySelectorAll('.pswp')[0];

console.log('here!!', pswpElement);

// build items array
let items = [
    {
        src: 'https://placekitten.com/600/400',
        w: 600,
        h: 400
    },
    {
        src: 'https://placekitten.com/1200/900',
        w: 1200,
        h: 900
    }
];

// define options (if needed)
let options = {
    // optionName: 'option value'
    // for example:
    index: 0 // start at first slide
};

// Initializes and opens PhotoSwipe
let gallery = new PhotoSwipe( pswpElement, PhotoSwipeUI_Default, items, options);
gallery.init();