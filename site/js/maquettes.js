// Améliorations des maquettes uniquement ; aucun stockage ni appel de réservation.
(() => {
  const switcher = document.querySelector('.view-switch');
  if (switcher) {
    switcher.hidden = false;
    switcher.addEventListener('click', event => {
      const button = event.target.closest('[data-preview-view]');
      if (!button) return;
      const view = button.dataset.previewView;
      switcher.querySelectorAll('button').forEach(item => item.setAttribute('aria-pressed', String(item === button)));
      document.querySelectorAll('.direction__preview img').forEach(img => {
        img.src = img.dataset[view];
        img.alt = `${view === 'hero' ? 'Accueil' : 'Prestations'} de la composition ${img.dataset.name}`;
      });
    });
  }

  const tablist = document.querySelector('[data-service-tabs]');
  if (!tablist) return;
  const tabs = [...tablist.querySelectorAll('a')];
  const panels = tabs.map(tab => document.querySelector(tab.getAttribute('href')));
  tablist.setAttribute('role', 'tablist');
  function select(index, focus = false) {
    tabs.forEach((tab, i) => {
      tab.setAttribute('aria-selected', String(i === index));
      tab.tabIndex = i === index ? 0 : -1;
      panels[i].hidden = i !== index;
    });
    if (focus) tabs[index].focus();
  }
  tabs.forEach((tab, index) => {
    tab.setAttribute('role', 'tab');
    tab.setAttribute('aria-controls', panels[index].id);
    panels[index].setAttribute('role', 'tabpanel');
    panels[index].setAttribute('aria-labelledby', tab.id);
    panels[index].tabIndex = 0;
    tab.addEventListener('click', event => { event.preventDefault(); select(index); });
    tab.addEventListener('keydown', event => {
      let next;
      if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
      if (event.key === 'ArrowLeft') next = (index + tabs.length - 1) % tabs.length;
      if (event.key === 'Home') next = 0;
      if (event.key === 'End') next = tabs.length - 1;
      if (next !== undefined) { event.preventDefault(); select(next, true); }
    });
  });
  const requested = panels.findIndex(panel => '#' + panel.id === window.location.hash);
  select(requested >= 0 ? requested : 0);
})();
