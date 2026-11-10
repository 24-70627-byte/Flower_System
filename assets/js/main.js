// Nicole's Flowers & Greens - Main JS
document.addEventListener('DOMContentLoaded', function () {
  // Bootstrap tooltips
  if (window.bootstrap) {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });
  }

  // Header search drawer
  var searchToggle = document.getElementById('searchToggle');
  var searchDrawer = document.getElementById('searchDrawer');
  if (searchToggle && searchDrawer) {
    searchToggle.addEventListener('click', function () {
      searchDrawer.classList.toggle('open');
      if (searchDrawer.classList.contains('open')) {
        var input = searchDrawer.querySelector('input');
        if (input) input.focus();
      }
    });
  }

  // Password show / hide
  document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
    button.addEventListener('click', function () {
      var input = document.getElementById(button.getAttribute('data-toggle-password'));
      if (!input) return;
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      var icon = button.querySelector('i');
      if (icon) {
        icon.classList.toggle('fa-eye', !show);
        icon.classList.toggle('fa-eye-slash', show);
      }
    });
  });

  // Character counters
  document.querySelectorAll('[data-char-count]').forEach(function (field) {
    var target = document.getElementById(field.getAttribute('data-char-count'));
    var update = function () {
      if (target) target.textContent = field.value.length;
    };
    field.addEventListener('input', update);
    update();
  });

  // Selectable radio cards
  document.querySelectorAll('.radio-card input[type="radio"]').forEach(function (radio) {
    radio.addEventListener('change', function () {
      var group = radio.closest('.radio-cards');
      if (!group) return;
      group.querySelectorAll('.radio-card').forEach(function (card) {
        card.classList.toggle('selected', card.contains(radio) && radio.checked);
      });
    });
  });

  // Row links use a real link inside; stop it firing twice from the row handler.
  document.querySelectorAll('tr[onclick] a').forEach(function (link) {
    link.addEventListener('click', function (event) {
      event.stopPropagation();
    });
  });

  // Auto-hide alerts after 6 seconds
  setTimeout(function () {
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function (alert) {
      if (window.bootstrap) {
        var bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
      }
    });
  }, 6000);
});
