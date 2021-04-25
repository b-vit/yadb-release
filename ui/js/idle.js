$(document).idle({
    onIdle: function(){
        document.location.href = '/idle';
    },
    idle: 900000, // 15 minut, dalo by se to nastavovat v konfiguraci? Mělo by.
    startAtIdle: true
})