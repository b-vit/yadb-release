$(document).ready($('#buttonsCR').hide());
function openKBFunction() {
    $('#keyboard').show();
    $('#buttonsCR').show();
    $('#openkb').hide();
}

function closeKBFunction() {
    $('#keyboard').hide();
    $('#buttonsCR').hide();
    $('#openkb').show();
}

function toggleKBFunction(){
    $('#keyboard').toggle();
    $('#buttonsCR').toggle();
    $('#openkb').toggle();
    scrollUp();
}

function scrollUp(){
    $('html, body').animate({scrollTop: '0px'}, 300);
}

function scrollDown(){
    $('html, body').animate({scrollTop: $(document).height()}, 100);
}

$(document).ready(function() {
    var table = $('#main_table').DataTable( {
        "bLengthChange": false,
        "bFilter": true,
        "scrollY": "900px",
        "scrollCollapse": true,
        "paging":true,
        "order": [[ 1, "desc" ]],
        "language": {
            "sEmptyTable":     "Tabulka neobsahuje žádná data",
            "sInfo":           "Zobrazuji _START_ až _END_ z celkem _TOTAL_ záznamů",
            "sInfoEmpty":      "Zobrazuji 0 až 0 z 0 záznamů",
            "sInfoFiltered":   "(filtrováno z celkem _MAX_ záznamů)",
            "sInfoPostFix":    "",
            "sInfoThousands":  " ",
            "sLengthMenu":     "Zobraz záznamů _MENU_",
            "sLoadingRecords": "Načítám...",
            "sProcessing":     "Provádím...",
            "sSearch":         "Hledat:",
            "sZeroRecords":    "Žádné záznamy nebyly nalezeny",
            "oPaginate": {
                "sFirst":    "První",
                "sLast":     "Poslední",
                "sNext":     "Další",
                "sPrevious": "Předchozí"
            },
            "oAria": {
                "sSortAscending":  ": aktivujte pro řazení sloupce vzestupně",
                "sSortDescending": ": aktivujte pro řazení sloupce sestupně"
            }
        }
    } );

    table.search($('#filter').val()).draw();

    $(".dataTables_filter").hide();
    $(".dataTables_info").hide();

    $('#table_input').on('keyup change', function () {
        table.search( this.value ).draw();
    });

    $('#table_input').on('click', function () {
        openKBFunction();
    });

    $('#nextCustom').on( 'click', function () {
        table.page( 'next' ).draw( 'page' );
    } );

    $('#previousCustom').on( 'click', function () {
        table.page( 'previous' ).draw( 'page' );
    } );


    const czech = {
        default: [
            "1 2 3 4 5 6 7 8 9 0 %",
            "+ \u011B \u0161 \u010D \u0159 \u017E \u00FD \u00E1 \u00ED \u00E9 \u00B4 {bksp}",
            "{tab} q w e r t y u i o p \u00FA ) \u00A8",
            "{shift} a s d f g h j k l \u016F \u00A7 {enter}",
            "\\ z x c v b n m , . - {shift}",
            "{space}"
        ],
        shift: [
            "\u00b0 1 2 3 4 5 6 7 8 9 0 % \u02c7",
            "+ \u011B \u0161 \u010D \u0159 \u017E \u00FD \u00E1 \u00ED \u00E9 \u00B4 {bksp}",
            "{tab} Q W E R T Y U I O P / ( '",
            '{shift} A S D F G H J K L " ! {enter}',
            "| Z X C V B N M , . _ {shift}",
            "{space}"
        ]
    };


    let Keyboard = window.SimpleKeyboard.default;

    let keyboard = new Keyboard({
        onChange: input => onChange(input),
        onKeyPress: button => onKeyPress(button),
        layout: czech
    });

    document.getElementById('delete_input').onclick = function(){deleteInput()};

    function deleteInput() {
        document.getElementById('table_input').value = '';
        keyboard.clearInput();
        table.search("").draw();
    }

    document.querySelector(".input").addEventListener("input", event => {
        keyboard.setInput(event.target.value);
    });

    console.log(keyboard);

    function onChange(input) {
        document.querySelector(".input").value = input;
        console.log("Input changed", input);
    }

    function onKeyPress(button) {
        console.log("Button pressed", button);
        if (button === "{shift}" || button === "{lock}") handleShift();
        $("#table_input").trigger("change");
    }

    function handleShift() {
        let currentLayout = keyboard.options.layoutName;
        let shiftToggle = currentLayout === "default" ? "shift" : "default";

        keyboard.setOptions({
            layoutName: shiftToggle
        });
    }

} );

