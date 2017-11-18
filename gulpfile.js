var gulp        = require('gulp');
var browserSync = require('browser-sync').create();
var reload      = browserSync.reload;

// Include plugins
var plugins = require('gulp-load-plugins')();                       // load all plugins

var files = {
  scss:   'src/sass/**/*.scss',
  css:    '**/*.css',
  js:     'src/js/**/*.js',
  twig:   'templates/**/*.twig',
  php:    '**/*.php',
  images: 'src/images/*.{png,jpg,svg}'
};

// Create helpful error mesages.
var showError = function (error) {
  var report = '';
  var color = plugins.util.colors.white.bgRed;
  var task = error.plugin;
  var prob;
  var file;
  var line;
  var cause;

  if (task === 'gulp-uglify') {
    prob = error.message;
    if (error.cause) {
      if (error.cause.message) {
        cause = error.cause.message;
      }
      if (error.cause.filename) {
        file = error.cause.filename;
      }
      if (error.cause.line) {
        line = error.cause.line;
      }
    }
  }
  else if (task === 'gulp-sass') {
    prob = error.formatted;
  }
  else {
    prob = error.message;
    if (error.fileName) {
      file = error.fileName;
    }
    if (error.lineNumber) {
      line = error.lineNumber;
    }
  }

  report += color('TASK:') + ' [' + task + ']\n';
  report += color('PROB:') + ' ' + prob + '\n';
  if (file) { report += color('FILE:') + ' ' + file + '\n'; }
  if (line) { report += color('LINE:') + ' ' + line + '\n'; }
  if (cause) { report += color('CASE:') + ' ' + cause + '\n'; }
  console.error(report);

  // Uncomment to inspect the error object.
  // console.log(error);

  // Prevent the watch task from stopping on errors.
  this.emit('end');
};


// JS
gulp.task('js', function() {
    return gulp.src('src/js/site.js')
        .pipe(plugins.sourcemaps.init())
        .pipe(plugins.plumber())
        .pipe(plugins.uglify())                                     // minify
        .pipe(plugins.sourcemaps.write('.'))
        .pipe(gulp.dest('dist/js'));
});

// Clean
gulp.task('clean', function() {
    return gulp.src('./dist/css', {read: false})
        .pipe(plugins.clean());
});

// Images optimisation
gulp.task('img', function () {
    return gulp.src(files.images)
        .pipe(plugins.imagemin())
        .pipe(gulp.dest('./dist/images'));
});

// BrowserSync
gulp.task('browser-sync', function(){

  browserSync.init(files, {
    proxy: 'localhost:8080',
    injectChanges: true
  });
  gulp.watch(files.css).on('change', reload);
  gulp.watch(files.scss, ['sass']).on('change', reload);
  gulp.watch([files.js, files.twig, files.php]).on('change', reload);
});

// SASS
gulp.task('sass', function(){
   var assets = require('postcss-assets');
   var autoprefixer = require('autoprefixer');
   var sass = require('gulp-sass');
   var sassGlob = require('gulp-sass-glob');
   var processors = [
     assets({
       loadPaths: ['./src/images/']
     }),
     autoprefixer({
       browsers: ['last 3 versions', '> 1%'],
       remove: false // Don’t remove outdated prefixes: about 10% faster.
     })
   ];
   return gulp.src(files.scss)
     .pipe(plugins.sourcemaps.init())
     .pipe(plugins.plumber({errorHandler: showError}))
     .pipe(sassGlob())
     .pipe(sass().on('error', sass.logError))
     .pipe(plugins.postcss(processors))
     .pipe(plugins.csso())
     .pipe(plugins.sourcemaps.write(''))
     .pipe(gulp.dest('./dist/css'))
     .pipe(browserSync.stream({match: '**/*.css'}));
     // .pipe(browserSync.reload({stream: true}));
});

//SVG2PNG
gulp.task('svg2png', function(){
  return gulp.src(['./src/images/bg/*.svg'], {base: '.'})
      .pipe(plugins.plumber({errorHandler: showError}))
      .pipe(plugins.svg2png())
      .pipe(gulp.dest('.'));
})

// Build
gulp.task('build', ['clean', 'sass', 'js', 'img']);

// Default
gulp.task('default', ['build', 'browser-sync']);
