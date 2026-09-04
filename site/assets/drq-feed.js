(function () {
  'use strict';
  var section = document.getElementById('drq-approved-update');
  if (!section) return;
  var controller = new AbortController();
  var timer = setTimeout(function () { controller.abort(); }, 12000);
  fetch('/drq-feed.php', { signal: controller.signal, credentials: 'omit' })
    .then(function (response) { if (!response.ok) throw new Error('Unavailable'); return response.json(); })
    .then(function (data) {
      var post = data.posts && data.posts[0];
      if (!post || post.id !== '109709817601324_1719423730192457') return;
      var imageUrl = new URL(post.image), linkUrl = new URL(post.url);
      if (imageUrl.protocol !== 'https:' || !/(^|\.)fbcdn\.net$/.test(imageUrl.hostname)) return;
      if (linkUrl.protocol !== 'https:' || !['www.facebook.com', 'facebook.com'].includes(linkUrl.hostname)) return;
      var img = section.querySelector('[data-post-image]');
      var lines = String(post.message || '').trim().split(/\r?\n/);
      section.querySelector('[data-post-title]').textContent = lines.shift();
      section.querySelector('[data-caption]').textContent = lines.join('\n').trim();
      var date = new Date(post.created_time);
      if (post.created_time && !isNaN(date.getTime())) {
        var time = section.querySelector('[data-post-date]');
        time.dateTime = date.toISOString();
        time.textContent = new Intl.DateTimeFormat('en-AU', {day:'numeric',month:'long',year:'numeric',timeZone:'Australia/Darwin'}).format(date);
        time.hidden = false;
      }
      section.querySelector('[data-post-link]').href = post.url;
      img.onload = function () { section.hidden = false; };
      img.onerror = function () { section.hidden = true; };
      img.src = post.image;
    })
    .catch(function () { section.hidden = true; })
    .finally(function () { clearTimeout(timer); });
}());
