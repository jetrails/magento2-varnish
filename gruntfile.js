module.exports = function ( grunt ) {

	// Import the package settings and the staging configuration
	var package = require ("./package.json");

	// Load NPM tasks so we can use them in our configuration
	grunt.loadNpmTasks ("grunt-contrib-compress");
	grunt.loadNpmTasks ("grunt-replace");
	grunt.loadNpmTasks ("grunt-mkdir");

	grunt.task.registerTask ( "init", "initialize directories", function () {
		grunt.config.set ( "mkdir.execute.options.create", [ "dist" ] );
		grunt.task.run ( "mkdir:execute" );
	});

	grunt.task.registerTask ( "version", "update version as defined in package.json", function () {

	});

	grunt.task.registerTask ( "package", "compress module into zip file", function () {

	});

	grunt.task.registerTask ( "default", "preform a full build", [ "init", "version", "package" ] );

}