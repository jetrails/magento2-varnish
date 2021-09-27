"use strict"

require (["jquery"], function ( $ ) {

	$(document).on ( "click", ".varnish .tray-labels [data-tray]", function () {
		const tray = $(this).data ("tray")
		const open = $(this).data ("open")
		$(this).parents (".card").find (".tray-labels [data-tray]").data ( "open", false )
		$(this).parents (".card").find (".tray-labels [data-tray]").removeClass ("active")
		$(this).parents (".card").find (".tray-labels [data-tray] span").html ("&blacktriangleright;")
		$(this).parents (".card").find (".tray[data-tray]").css ( "display", "none" )
		if ( open != true ) {
			$(this).data ( "open", true )
			$(this).addClass ("active")
			$(this).find ("span").html ("&blacktriangledown;")
			$(this).parents (".card").find (`.tray[data-tray='${tray}']`).css ( "display", "block" )
		}
	})

})
