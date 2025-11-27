// Auto-aplicar filtros de Orchid al cambiar opciones
(function(){
  // Permite configurar tiempos globales si es necesario
  const CFG = window.ORCHID_AUTO_FILTER || {
    singleDelay: 500,   // ms para select de un solo valor
    multiDelay: 1500,   // ms para select múltiple
    enabled: false,     // opt-in por vista
  };

  function isEnabled(){
    // 1) Variable global
    if (CFG && CFG.enabled === true) return true;
    // 2) Meta tag de activación
    const meta = document.querySelector('meta[name="auto-filter"][content="on"]');
    if (meta) return true;
    // 3) Marcador en el DOM
    if (document.querySelector('[data-auto-filter="on"]')) return true;
    return false;
  }

  function isMultiSelect(el){
    try {
      if (!el || el.tagName !== 'SELECT') return false;
      // Para Select/Relation de Orchid, el atributo multiple existe cuando es múltiple
      return el.hasAttribute('multiple');
    } catch (_) {
      return false;
    }
  }

  function initAutoFilter(){
    if (!isEnabled()) return;
    const containers = document.querySelectorAll('[data-controller="filter"]');
    containers.forEach((container) => {
      if (container.dataset.autoFilterBound === '1') return;
      container.dataset.autoFilterBound = '1';

      container.addEventListener('change', (e) => {
        const el = e.target;
        const isSelect = el && el.tagName === 'SELECT';
        const isRadio = el && el.type === 'radio';
        const isCheckbox = el && el.type === 'checkbox';
        // Evita auto-submit mientras se escribe en inputs de texto
        if (!isSelect && !isRadio && !isCheckbox) return;
        // Ajusta la espera si el select es múltiple
        const delay = isSelect && isMultiSelect(el) ? CFG.multiDelay : CFG.singleDelay;

        // Debounce para evitar múltiples submits rápidos
        clearTimeout(container._autoFilterTimer);
        container._autoFilterTimer = setTimeout(() => {
          try {
            const form = document.getElementById('filters');
            if (!form) return;

            // Dispara el evento que los campos escuchan antes de submit
            form.dispatchEvent(new Event('orchid:screen-submit'));

            // Usa el controlador Stimulus de Orchid para aplicar filtros
            if (window.application) {
              const ctrl = window.application.getControllerForElementAndIdentifier(container, 'filter');
              if (ctrl && typeof ctrl.setAllFilter === 'function') {
                ctrl.setAllFilter();
                return;
              }
            }

            // Alternativa: simular click en el botón "Apply" si existe
            const applyBtn = document.querySelector('button[form="filters"][type="submit"]');
            if (applyBtn) applyBtn.click();
          } catch (err) {
            // Silencioso: no romper UI si algo falla
          }
        }, delay);
      });
    });
  }

  // Con Turbo en Orchid, usar turbo:load para re-anclar handlers
  document.addEventListener('turbo:load', initAutoFilter);
  // Fallback para primera carga si no está Turbo
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAutoFilter);
  } else {
    initAutoFilter();
  }
})();