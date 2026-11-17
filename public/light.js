// OWNER: Claude Rollins | live traffic light: counts down every second and follows the server
(function () {
  var lampIds = { RED: 'lamp-red', YELLOW: 'lamp-amber', GREEN: 'lamp-green' };
  var lampClass = { RED: 'red', YELLOW: 'amber', GREEN: 'green' };
  var phase = null, endsAt = null, emergency = false, waiting = false;

  function el(id) { return document.getElementById(id); }
  if (!el('light')) return;                         // the login page has no light

  function draw() {
    if (phase === null) return;
    for (var p in lampIds) {                        // switch the right lamp on, the others off
      el(lampIds[p]).className = 'lamp' + (p === phase ? ' ' + lampClass[p] : '');
    }
    el('stat-signal').textContent = phase;
    if (emergency) {                                // BR-02: no countdown during an emergency
      el('count').textContent = 'EMERGENCY';
      el('count-label').textContent = 'Override active';
      return;
    }
    var left = Math.max(0, Math.ceil((endsAt - Date.now()) / 1000));
    el('count').textContent = left + 's';
    el('count-label').textContent = phase.charAt(0) + phase.slice(1).toLowerCase() + ' time left';
    if (left === 0 && !waiting) { waiting = true; sync(); }   // time is up: ask for the next light
  }

  function sync() {                                 // ask the server which light is on and for how long
    fetch('index.php?state=1', { cache: 'no-store' })
      .then(function (r) { return r.json(); })
      .then(function (s) {
        if (!s) return;
        phase = s.phase;
        emergency = s.emergency;
        endsAt = emergency ? null : Date.now() + s.remaining * 1000;
        el('stat-green').textContent = s.green + 's';
        draw();
      })
      .catch(function () {})
      .then(function () { waiting = false; });
  }

  setInterval(draw, 200);                           // redraw the countdown 5 times a second
  setInterval(sync, 2000);                          // re-check the server every 2 seconds
  sync();
})();