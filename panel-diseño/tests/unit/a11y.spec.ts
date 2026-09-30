import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import axe from 'axe-core';
import BaseButton from '@/components/base/BaseButton.vue';

describe('a11y · BaseButton', () => {
  it('no presenta violaciones axe-core', async () => {
    const wrapper = mount(BaseButton, {
      attachTo: document.body,
      slots: { default: 'Guardar cambios' },
    });
    const results = await axe.run(wrapper.element as HTMLElement);
    expect(results.violations).toEqual([]);
    wrapper.unmount();
  });
});
