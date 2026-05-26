/**
 * Splash d'accueil portail (même animation que l'inscription retraite).
 */
(function () {
  const splash = document.getElementById('retraiteGateSplash');
  const page = document.querySelector('.portail-page');

  if (!splash) {
    if (page) {
      page.classList.remove('portail-page--booting');
    }
    return;
  }

  const finish = function () {
    splash.classList.remove('hold');
    splash.classList.add('exit');
    window.setTimeout(function () {
      splash.remove();
      if (page) {
        page.classList.remove('portail-page--booting');
      }
    }, 650);
  };

  window.setTimeout(finish, 2000);
})();
