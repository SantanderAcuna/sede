import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import BaseButton from '@/components/base/BaseButton.vue';

describe('BaseButton', () => {
  it('renderiza el slot', () => {
    const w = mount(BaseButton, { slots: { default: 'Guardar' } });
    expect(w.text()).toContain('Guardar');
  });

  it('emite click', async () => {
    const w = mount(BaseButton);
    await w.trigger('click');
    expect(w.emitted('click')).toHaveLength(1);
  });

  it('queda deshabilitado en loading', () => {
    const w = mount(BaseButton, { props: { loading: true } });
    expect(w.attributes('disabled')).toBeDefined();
    expect(w.attributes('aria-busy')).toBe('true');
  });

  it('aplica variant danger', () => {
    const w = mount(BaseButton, { props: { variant: 'danger' } });
    expect(w.classes().some(c => c.includes('red'))).toBe(true);
  });
});
