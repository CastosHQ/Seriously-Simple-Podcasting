module.exports = function (a) {
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
					src: ['assets/js/*.js', '!assets/js/*.min.js'],
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
			dist: {
				options: {
					sourcemap: false,
					compress: false,
					yuicompress: false,
					style: 'expanded',
				},
				files: {
					'assets/admin/css/admin.css' : 'assets/admin/scss/all.scss'
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
				files: [{
					expand: true,
					cwd: 'assets/css',
					src: ['*.css', '!*.min.css'],
					dest: 'assets/css',
					ext: '.min.css'
				}]
			}
		},
	});
	a.loadNpmTasks('grunt-contrib-cssmin');
	a.loadNpmTasks('grunt-contrib-uglify');
	a.loadNpmTasks('grunt-contrib-sass');
	a.loadNpmTasks('grunt-contrib-watch');
	a.registerTask('default',['watch']);
/*
	a.loadNpmTasks("grunt-wp-i18n");
	a.loadNpmTasks("grunt-wp-readme-to-markdown");
	a.registerTask("default", ["i18n", "readme"]);
	a.registerTask("i18n", ["addtextdomain", "makepot"]);
	a.registerTask("readme", ["wp_readme_to_markdown"]);
*/
	a.util.linefeed = "\n"
};
