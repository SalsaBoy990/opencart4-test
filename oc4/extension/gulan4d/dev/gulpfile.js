const proxyURL = 'localhost/opencart/';

// Load Gulp...of course
const { src, dest, task, watch, series, parallel } = require('gulp');

// CSS related plugins
const sass = require('gulp-sass')(require('sass'));
const concatCss = require('gulp-concat-css');
let cleanCSS = require('gulp-clean-css');

// JS related plugins
const uglify = require('gulp-uglify');
const babelify = require('babelify');
const browserify = require('browserify');
const source = require('vinyl-source-stream');
const buffer = require('vinyl-buffer');
const stripDebug = require('gulp-strip-debug');

// Utility plugins
const rename = require('gulp-rename');
const sourcemaps = require('gulp-sourcemaps');
const notify = require('gulp-notify');
const plumber = require('gulp-plumber');
const options = require('gulp-options');
const gulpif = require('gulp-if');

// Browers related plugins
const browserSync = require('browser-sync').create();

// Project related variables
const styleSRC = './scss/*.scss';
const styleURL = './../catalog/view/stylesheet/';
const mapURL = './';

const jsSRC = './js/';
const jsFront = 'custom.js';
const jsFiles = [jsFront];
const jsURL = './../catalog/view/js/';

const imgSRC = './image/**/*';
const imgURL = './../catalog/view/image/';

const fontsSRC = './fonts/**/*';
const fontsURL = './../catalog/view/fonts/';

const styleWatch = './scss/**/*.scss';
const jsWatch = './js/**/*.js';
const imgWatch = './image/**/*.*';
const fontsWatch = './fonts/**/*.*';
const twigWatch = '../catalog/view/template/**/*.twig';

// Tasks
function browser_sync() {
    browserSync.init({
        proxy: proxyURL
    });
}

function reload(done) {
    browserSync.reload();
    done();
}

function css(done) {
    src([styleSRC])
        .pipe(sourcemaps.init())
        .pipe(
            sass({
                errLogToConsole: true,
                outputStyle: 'compressed'
            })
        )
        .pipe(concatCss('stylesheet.css'))
        .pipe(cleanCSS({ compatibility: 'ie8' }))
        .on('error', console.error.bind(console))
        //.pipe(rename({ suffix: '.min' }))
        .pipe(sourcemaps.write(mapURL))
        .pipe(dest(styleURL))
        .pipe(browserSync.stream());
    done();
}

function js(done) {
    jsFiles.map(function(entry) {
        return browserify({
            entries: [jsSRC + entry]
        })
            .transform(babelify, { presets: ['@babel/preset-env'] })
            .bundle()
            .pipe(source(entry))
            .pipe(
                rename({
                    extname: '.min.js'
                })
            )
            .pipe(buffer())
            .pipe(gulpif(options.has('production'), stripDebug()))
            .pipe(sourcemaps.init({ loadMaps: true }))
            .pipe(uglify())
            .pipe(sourcemaps.write('.'))
            .pipe(dest(jsURL))
            .pipe(browserSync.stream());
    });
    done();
}

function triggerPlumber(src_file, dest_file) {
    return src(src_file)
        .pipe(plumber())
        .pipe(dest(dest_file));
}

function image() {
    return triggerPlumber(imgSRC, imgURL);
}

function fonts() {
    return triggerPlumber(fontsSRC, fontsURL);
}

function watch_files() {
    watch(styleWatch, series(css, reload));
    watch(jsWatch, series(js, reload));
    watch(imgWatch, series(image, reload));
    watch(fontsWatch, series(fonts, reload));
    watch(twigWatch, series(reload));
    src(jsURL + 'custom.min.js').pipe(
        notify({ message: 'Gulp is Watching, Happy Coding!' })
    );
}

task('css', css);
task('js', js);
task('image', image);
task('fonts', fonts);
task('default', parallel(css, js, image, fonts));
task('watch', parallel(browser_sync, watch_files));
