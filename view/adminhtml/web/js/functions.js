"use strict"

require (["jquery"], function ( $ ) {

	if ( $(".button-container.steps").length > 0 ) {

		const container = $(".varnish .button-container")
		const previous = $(container).find (".previous")
		const next = $(container).find (".next")
		const message = $(container).find (".title")
		const count = $(".varnish .card[data-section]").length

		function loadStage ( index ) {
			const target = $(".varnish .card[data-section]").eq ( index ).data ("section")
			container.data ( "stage", index )
			$(message).text (`Step ${index + 1} of ${count}: ${target}`)
			$(".cards .card").css ( "display", "none" )
			$(".cards .card").eq ( index ).css ( "display", "block" )
			$(previous) [ index == 0 ? "removeClass" : "addClass" ] ("active")
			$(next) [ index >= count - 1 ? "removeClass" : "addClass" ] ("active")
		}

		$(document).on ( "click", ".previous.active", () => {
			const stage = container.data ("stage")
			loadStage ( Math.max ( 0, stage - 1 ) )
		})

		$(document).on ( "click", ".next.active", () => {
			const stage = container.data ("stage")
			loadStage ( Math.min ( count - 1, stage + 1 ) )
		})

		loadStage ( container.data ("stage") )

	}

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
