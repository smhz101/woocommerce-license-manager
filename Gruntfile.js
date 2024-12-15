module.exports = function (grunt) {
	"use strict";

	// Load all Grunt tasks matching the `grunt-*` pattern
	require("load-grunt-tasks")(grunt);

	// Project configuration
	grunt.initConfig({
		pkg: grunt.file.readJSON("package.json"),

		// Set plugin slug (e.g., 'woocommerce-license-manager')
		plugin_slug: "woocommerce-license-manager",

		// Paths for tasks
		paths: {
			languages: "languages",
			build: "build",
			temp: "temp",
		},

		// Clean task to remove build and temp directories
		clean: {
			build: ["<%= paths.build %>/", "<%= paths.temp %>/"],
		},

		// Make a copy of the plugin for the build
		copy: {
			build: {
				expand: true,
				src: [
					"**",
					"!node_modules/**",
					"!build/**",
					"!temp/**",
					"!Gruntfile.js",
					"!package.json",
					"!package-lock.json",
					"!**/*.zip",
					"!**/*.md",
					"!**/*.bak",
					"!**/*.gitignore",
					"!**/*.sh",
					"!tests/**",
					"!**/*.scss",
					"!**/*.map",
					"!**/.git/**",
					"!**/.svn/**",
					"!**/.DS_Store",
					"!**/Thumbs.db",
				],
				dest: "<%= paths.temp %>/<%= plugin_slug %>/",
			},
		},

		// Compile .po files to .mo files
		po2mo: {
			files: {
				src: "<%= paths.languages %>/*.po",
				expand: true,
			},
		},

		// Compress task to create the ZIP file
		compress: {
			build: {
				options: {
					archive: "<%= paths.build %>/<%= plugin_slug %>.zip",
					mode: "zip",
				},
				expand: true,
				cwd: "<%= paths.temp %>/<%= plugin_slug %>/",
				src: ["**/*"],
				dest: "<%= plugin_slug %>/",
			},
		},

		// Add text domain to PHP files
		addtextdomain: {
			options: {
				textdomain: "woocommerce-license-manager",
			},
			update_all_domains: {
				options: {
					updateDomains: true,
				},
				src: [
					"*.php",
					"**/*.php",
					"!node_modules/**",
					"!build/**",
					"!temp/**",
					"!tests/**",
					"!vendor/**",
				],
			},
		},

		// Convert readme.txt to README.md
		wp_readme_to_markdown: {
			your_target: {
				files: {
					"README.md": "readme.txt",
				},
			},
		},

		// Generate .pot file
		makepot: {
			target: {
				options: {
					domainPath: "/languages", // Where to save the POT file.
					exclude: [
						".git/*",
						"bin/*",
						"build/*",
						"node_modules/*",
						"temp/*",
						"tests/*",
						"vendor/*",
					],
					mainFile: "woocommerce-license-manager.php", // Main project file.
					potFilename: "<%= plugin_slug %>.pot", // Name of the POT file.
					type: "wp-plugin", // Type of project (wp-plugin or wp-theme).
					updateTimestamp: true,
					updatePoFiles: true, // Automatically update .po files.
					processPot: function (pot, options) {
						pot.headers["report-msgid-bugs-to"] =
							"https://wpthemepress.com/";
						pot.headers["last-translator"] =
							"Muzammil Hussain <sayhi@muzammil.dev>";
						pot.headers["language-team"] =
							"Muzammil Hussain <sayhi@muzammil.dev>";
						return pot;
					},
				},
			},
		},
	});

	// Load tasks
	grunt.loadNpmTasks("grunt-wp-i18n");
	grunt.loadNpmTasks("grunt-wp-readme-to-markdown");
	grunt.loadNpmTasks("grunt-contrib-clean");
	grunt.loadNpmTasks("grunt-contrib-compress");
	grunt.loadNpmTasks("grunt-contrib-copy");
	grunt.loadNpmTasks("grunt-po2mo");

	// Register tasks
	grunt.registerTask("lang", ["makepot", "po2mo"]);
	grunt.registerTask("readme", ["wp_readme_to_markdown"]);
	grunt.registerTask("build", [
		"clean",
		// "lang",
		"readme",
		"copy",
		"compress",
	]);
	grunt.registerTask("default", ["build"]);

	grunt.util.linefeed = "\n";
};
