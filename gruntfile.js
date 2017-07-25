module.exports = function ( grunt ) {

	// Import the package settings and the staging configuration
	var package = require ("./package.json");

	// Load NPM tasks so we can use them in our configuration
	grunt.loadNpmTasks ("grunt-contrib-compress");
	grunt.loadNpmTasks ("grunt-contrib-watch");
	grunt.loadNpmTasks ("grunt-replace");
	grunt.loadNpmTasks ("grunt-mkdir");
	grunt.loadNpmTasks ("grunt-rsync");

	grunt.task.registerTask ( "init", "initialize directories", function () {
		grunt.config.set ( "mkdir.execute.options.create", [ "dist" ] );
		grunt.task.run ( "mkdir:execute" );
	});

	grunt.task.registerTask ( "deploy", "sync to production environment", function () {
		grunt.config.set ( "rsync", {
			options: {
			    args: [ "--quiet" ],
			    recursive: true
			},
			stage: {
			    options: {
			        src: "./src/app",
			        dest: "/Users/raffi/Desktop/Magento-2-Staging/",
			        delete: false
			    }
			}
		});
		grunt.task.run ( "rsync:stage" );
	});

	grunt.task.registerTask ( "stream", "watch all files and deploy on change", function () {
		grunt.task.run ( "deploy" );
		grunt.config.set ( "watch", {
		    src: {
		    	files: [ "./src/**/*" ],
		    	tasks: [ "deploy" ]
		    }
		});
		grunt.task.run ( "watch:src" );
	});

	grunt.task.registerTask ( "version", "update version as defined in package.json", function () {

	});

	grunt.task.registerTask ( "package", "compress module into zip file", function () {

	});

	grunt.task.registerTask ( "default", "preform a full build", [ "init", "version", "deploy", "package" ] );

}