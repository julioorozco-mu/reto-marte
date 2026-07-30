document.addEventListener('DOMContentLoaded', () => {
  const sidebar = document.getElementById('sidebar');
  const toggle = document.querySelector('[data-sidebar-toggle]');

  if (toggle && sidebar) {
    toggle.addEventListener('click', () => {
      sidebar.classList.toggle('open');
    });

    document.addEventListener('click', (event) => {
      if (!sidebar.classList.contains('open')) return;
      const target = event.target;
      if (target instanceof Node && !sidebar.contains(target) && target !== toggle) {
        sidebar.classList.remove('open');
      }
    });
  }
});
