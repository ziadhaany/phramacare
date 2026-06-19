$(document).ready(function () {
  const fields = {
    fname: $("#fname"),
    lname: $("#lname"),
    email: $("#email"),
    phone: $("#phone"),
    password: $("#password"),
    confirm: $("#confirm"),
    street: $("#street"),
    city: $("#city"),
    zipcode: $("#zipcode")
  };

  const errors = {
    fname: $("#err-fname"),
    lname: $("#err-lname"),
    email: $("#err-email"),
    phone: $("#err-phone"),
    password: $("#err-password"),
    confirm: $("#err-confirm"),
    street: $("#err-street"),
    city: $("#err-city"),
    zipcode: $("#err-zipcode")
  };

  const lettersRegex = /^[A-Za-z\u0600-\u06FF\s'-]+$/;
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const phoneRegex = /^\+?[0-9]{11,15}$/;

  function validateLetters($field, msg) {
    const val = $field.val().trim();
    if (val === "") {
      errors[$field.attr('id')].text("");
      $field.removeClass('valid invalid');
      return false;
    }
    const valid = lettersRegex.test(val);
    if (!valid) {
      errors[$field.attr('id')].text(msg);
      $field.addClass('invalid').removeClass('valid');
    } else {
      errors[$field.attr('id')].text('');
      $field.addClass('valid').removeClass('invalid');
    }
    return valid;
  }

  function validatePhone($field, msg) {
    const val = $field.val().trim();
    if (val === "") {
      errors[$field.attr('id')].text("");
      $field.removeClass('valid invalid');
      return false;
    }
    const valid = phoneRegex.test(val);
    if (!valid) {
      errors[$field.attr('id')].text(msg);
      $field.addClass('invalid').removeClass('valid');
    } else {
      errors[$field.attr('id')].text('');
      $field.addClass('valid').removeClass('invalid');
    }
    return valid;
  }

  function validateEmail($field, msg) {
    const val = $field.val().trim();
    if (val === "") {
      errors[$field.attr('id')].text("");
      $field.removeClass('valid invalid');
      return false;
    }
    const valid = emailRegex.test(val);
    if (!valid) {
      errors[$field.attr('id')].text(msg);
      $field.addClass('invalid').removeClass('valid');
    } else {
      errors[$field.attr('id')].text('');
      $field.addClass('valid').removeClass('invalid');
    }
    return valid;
  }

  function validatePasswords() {
    let ok = true;
    const pwdVal = fields.password.val();
    const confVal = fields.confirm.val();

    if (pwdVal === "") {
      errors.password.text("");
      fields.password.removeClass("valid invalid");
      ok = false;
    } else if (pwdVal.length < 6) {
      errors.password.text("Password must be at least 6 characters");
      fields.password.addClass("invalid").removeClass("valid");
      ok = false;
    } else {
      errors.password.text("");
      fields.password.addClass("valid").removeClass("invalid");
    }

    if (confVal === "") {
      errors.confirm.text("");
      fields.confirm.removeClass("valid invalid");
      ok = false;
    } else if (confVal !== pwdVal) {
      errors.confirm.text("Passwords do not match");
      fields.confirm.addClass("invalid").removeClass("valid");
      ok = false;
    } else {
      errors.confirm.text("");
      fields.confirm.addClass("valid").removeClass("invalid");
    }

    return ok;
  }

  function validateAddressCity($field, msg) {
    const val = $field.val().trim();
    if (val === "") {
      errors[$field.attr('id')].text('');
      $field.removeClass('valid invalid');
      return true; 
    }
    const words = val.split(/\s+/).filter(Boolean);
    if (words.length < 2) {
      errors[$field.attr('id')].text(msg);
      $field.addClass('invalid').removeClass('valid');
      return false;
    }

    const containsLetter = /[A-Za-z\u0600-\u06FF]/.test(val);
    const containsOnlyLettersAndSpaces = /^[A-Za-z\u0600-\u06FF\s'-]+$/.test(val);

    const valid = containsLetter && (containsOnlyLettersAndSpaces || /\d/.test(val));

    if (!valid) {
      errors[$field.attr('id')].text(msg);
      $field.addClass('invalid').removeClass('valid');
    } else {
      errors[$field.attr('id')].text('');
      $field.addClass('valid').removeClass('invalid');
    }
    return valid;
  }

  function validateCity($field, msg) {
    const val = $field.val().trim();
    if (val === "") {
      errors[$field.attr('id')].text('');
      $field.removeClass('valid invalid');
      return true; 
    }

    const containsLetter = /[A-Za-z\u0600-\u06FF]/.test(val);
    const containsOnlyLettersAndSpaces = /^[A-Za-z\u0600-\u06FF\s'-]+$/.test(val);

    const valid = containsLetter && (containsOnlyLettersAndSpaces || /\d/.test(val));

    if (!valid) {
      errors[$field.attr('id')].text(msg);
      $field.addClass('invalid').removeClass('valid');
    } else {
      errors[$field.attr('id')].text('');
      $field.addClass('valid').removeClass('invalid');
    }
    return valid;
  }

  function validateOptionalNumbers($field, msg) {
    if ($field.val().trim() === "") {
      errors[$field.attr('id')].text('');
      $field.removeClass('valid invalid');
      return true;
    }
    const valid = /^\d+$/.test($field.val().trim());
    if (!valid) {
      errors[$field.attr('id')].text(msg);
      $field.addClass('invalid').removeClass('valid');
    } else {
      errors[$field.attr('id')].text('');
      $field.addClass('valid').removeClass('invalid');
    }
    return valid;
  }

  $("#regForm").on("submit", function (e) {
    e.preventDefault();
    let valid = true;

    if (!validateLetters(fields.fname, "Letters only")) valid = false;
    if (!validateLetters(fields.lname, "Letters only")) valid = false;
    if (!validateEmail(fields.email, "Enter valid email")) valid = false;
    if (!validatePhone(fields.phone, "Phone must be 11-15 digits, optional +")) valid = false;
    if (!validatePasswords()) valid = false;

    if (fields.street.val().trim() !== "") {
      if (!validateAddressCity(fields.street, "Enter at least 2 words with letters (numbers optional)")) valid = false;
    }
    if (fields.city.val().trim() !== "") {
      if (!validateCity(fields.city, "Enter at least 1 character with letters (numbers optional)")) valid = false;
    }
    if (!validateOptionalNumbers(fields.zipcode, "Numbers only")) valid = false;

    if (valid) {
      $("#submitBtn").prop("disabled", true).text("Registering...");
      this.submit();
    }
  });

  // ================== REAL-TIME VALIDATION ==================
  fields.fname.on("input", function () {
    validateLetters(fields.fname, "Letters only");
  });

  fields.lname.on("input", function () {
    validateLetters(fields.lname, "Letters only");
  });

  fields.email.on("input", function () {
    validateEmail(fields.email, "Enter valid email");
  });

  fields.phone.on("input", function () {
    validatePhone(fields.phone, "Phone must be 11-15 digits, optional +");
  });

  fields.password.on("input", function () {
    validatePasswords();
  });

  fields.confirm.on("input", function () {
    validatePasswords();
  });

  fields.street.on("input", function () {
    validateAddressCity(fields.street, "Enter at least 2 words with letters (numbers optional)");
  });

  fields.city.on("input", function () {
    validateCity(fields.city, "Enter at least 1 character with letters (numbers optional)");
  });

  fields.zipcode.on("input", function () {
    validateOptionalNumbers(fields.zipcode, "Numbers only");
  });
});