module.exports = function (a) {
	const sass = require('sass');

	a.initConfig({
		pkg: a.file.readJSON("package.json"),
		addtextdomain: {
			options: {textdomain: "seriously-simple-podcasting"},
			update_all_domains: {
				options: {updateDomains: true},
				src: ["*.php", "**/*.php", "!.git/**/*", "!bin/**/*", "!node_modules/**/*", "!tests/**/*"]
			}
		},
		wp_readme_to_markdown: {your_target: {files: {"README.md": "readme.txt"}}},
		makepot: {
			target: {
				options: {
					domainPath: "/languages",
					exclude: [".git/*", "bin/*", "node_modules/*", "tests/*"],
					mainFile: "seriously-simple-podcasting.php",
					potFilename: "seriously-simple-podcasting.pot",
					potHeaders: {poedit: true, "x-poedit-keywordslist": true},
					type: "wp-plugin",
					updateTimestamp: true
				}
			}
		},
		uglify: {
			dev: {
				files: [{
					expand: true,
					src: ['assets/js/*.js', '!assets/js/*.min.js', 'assets/admin/js/*.js', '!assets/admin/js/*.min.js'],
					dest: 'assets/js',
					cwd: '.',
					rename: function (dst, src) {
						// To keep the source js files and make new files as `*.min.js`:
						// return src.replace('.js', '.min.js');
						// Or to override to src:
						// return src;
						return src.replace('.js', '.min.js');
					}
				}]
			}
		},
		sass: {
			options: {
				implementation: sass
			},
			dist: {
				options: {
					sourcemap: false,
					compress: false,
					yuicompress: false,
					style: 'expanded',
				},
				files: {
					'assets/admin/css/admin.css' : 'assets/admin/scss/all.scss',
					'assets/css/blocks/podcast-list.css' : 'src/scss/podcast-list.scss',
				}
			},
		},
		watch: {
			css: {
				files: '**/*.scss',
				tasks: ['sass']
			}
		},
		cssmin: {
			target: {
				files: [
					{
						expand: true,
						cwd: 'assets/css',
						src: ['*.css', '!*.min.css'],
						dest: 'assets/css',
						ext: '.min.css'
					},
					{
						expand: true,
						cwd: 'assets/css/blocks',
						src: ['*.css', '!*.min.css'],
						dest: 'assets/css/blocks',
						ext: '.min.css'
					},
					{
						expand: true,
						cwd: 'assets/admin/css',
						src: ['*.css', '!*.min.css'],
						dest: 'assets/admin/css',
						ext: '.min.css'
					},
				]
			}
		},
	});
	a.loadNpmTasks('grunt-contrib-cssmin');
	a.loadNpmTasks('grunt-contrib-uglify');
	a.loadNpmTasks('grunt-sass');
	a.loadNpmTasks('grunt-contrib-watch');
	a.registerTask('default',['sass', 'watch']);
/*
	a.loadNpmTasks("grunt-wp-i18n");
	a.loadNpmTasks("grunt-wp-readme-to-markdown");
	a.registerTask("default", ["i18n", "readme"]);
	a.registerTask("i18n", ["addtextdomain", "makepot"]);
	a.registerTask("readme", ["wp_readme_to_markdown"]);
*/
	a.util.linefeed = "\n"
};
