$('#sendBtn').on('click', () => {
    var email = $('#emailId').val();
    var name = $('#nameId').val();
    var pn = $('#phoneId').val();
    var city = $('#cityId').val();
    var service = $('#serviceId').val();
    var msg = $('#messageId').val();

    $.post('./php/index.php', {
            'email': email,
            'name': name,
            'phone': pn,
            'city': city,
            'service': service,
            'msg': msg
        });
})
