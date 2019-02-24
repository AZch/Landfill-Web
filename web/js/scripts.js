landfill = {
    create:function() {
        $.get('/form', {}, function(data) {
            $('#modal-content').html(data);
            $('#formModal').modal();
        });
    },

    edit: function(id) {
        $.get('/form/' + id, {}, function(data) {
                $('#modal-content').html(data);
                $('#formModal').modal();
            });
    }, 

    request: function(form, url, type) {
        $.ajax({
            url: url,
            type: type,
            data: new FormData(form),
            processData: false,
            contentType: false,
            success: function (data) {
                //console.log(json);


                console.log(data);


                var json = JSON.stringify(data);
                //console.log(json);
                if (json.result != 'OK')
                    wnd.alert(json.message);
                else
                    location.reload();
                location.reload();
            }
        });
    },

    submit: function(form) {
        landfill.request(form, '/landfilla', 'POST');
    }, 

    update: function(form, id) {
        landfill.request(form, '/landfill/' + id, 'POST');
    }, 

    del: function(id) {
        if (confirm('Подтвердите удаление')) {
            $.ajax({
                url: '/landfill/' + id,
                type: 'DELETE',
                success: function (data) {
                    location.reload();
                }
            });
        }
    }
}

wnd = {
    alert: function(text, time, color) {
        if(!color) {
            var color = 'red';
        }
        if (!time) {
            var time = 3000;
        }
        if ($('#wnd_alert').length != 0) {
            $('#wnd_alert').remove();
            clearTimeout(closeWndALert);
        }

        $('body').prepend('<div class="wnd_alert wnd_alert_'+color+'" id="wnd_alert">'+text+'</div>');
        $('#wnd_alert').fadeIn(300);
        closeWndALert = setTimeout(function() {
            $('#wnd_alert').fadeOut(300, function() {
                $(this).remove();
            })
        })
    }
}