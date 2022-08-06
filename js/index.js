$('#send-btn-form').on('click', () => {
    const email = $('#email-form').val().replace(/\s+/g,' ');
    const firstName = $('#grid-first-name').val().replace(/\s+/g,' ');
    const lastName = $('#grid-last-name').val().replace(/\s+/g,' ');
    const pn = $('#phn-form').val().replace(/\s+/g,' ');
    const city = $('#city-form').val().replace(/\s+/g,' ');
    const service = $('#service-from').val();
    const msg = $('#msg-form').val();

    const email_regex = /.*@.*\..*/gm;

    // Email Validation. Min.Email: name(min length: 1), Min Domain(2), min NameDomain(2)
    if (!(email_regex.test(email) || email.length < 7)) {
        alert('Неправильный(ая) номер телефона или почта');
        return false;
    }

    // Length Validation
    if (firstName.length < 2 || lastName.length < 2 || pn.length < 5 || city.length < 3 || service == '' || msg.length < 10) {
        alert('Введите все поля!');
        return false;
    }

    $.post('./php/index.php', {
        'email': email,
        'name': `${firstName} ${lastName}`,
        'phone': pn,
        'city': city,
        'service': service,
        'msg': msg
    });
})