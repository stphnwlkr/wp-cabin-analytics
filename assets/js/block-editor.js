( function( wp ) {
  const { registerBlockType } = wp.blocks;
  const { __ } = wp.i18n;
  const { InspectorControls } = wp.blockEditor || wp.editor;
  const { PanelBody, RadioControl, TextControl, ToggleControl } = wp.components;
  const ServerSideRender = wp.serverSideRender;

  registerBlockType('wp-cabin/analytics', {
    title: __('Cabin Analytics', 'wp-cabin-analytics'),
    icon: 'chart-area',
    category: 'widgets',
    attributes: {
      mode: { type: 'string', default: '' },
      range: { type: 'string', default: '' },
      domain: { type: 'string', default: '' },
      showFooter: { type: 'boolean', default: false },
    },
    edit: ( props ) => {
      const { attributes, setAttributes } = props;

      return [
        wp.element.createElement(
          InspectorControls,
          { key: 'inspector' },
          wp.element.createElement(
            PanelBody,
            { title: __('Cabin Analytics', 'wp-cabin-analytics'), initialOpen: true },
            wp.element.createElement(RadioControl, {
              label: __('Mode', 'wp-cabin-analytics'),
              selected: attributes.mode || '',
              options: [
                { label: __('Use plugin setting', 'wp-cabin-analytics'), value: '' },
                { label: __('Sparkline', 'wp-cabin-analytics'), value: 'sparkline' },
                { label: __('Chart', 'wp-cabin-analytics'), value: 'chart' },
              ],
              onChange: (v) => setAttributes({ mode: v }),
            }),
            wp.element.createElement(RadioControl, {
              label: __('Range', 'wp-cabin-analytics'),
              selected: attributes.range || '',
              options: [
                { label: __('Use plugin setting', 'wp-cabin-analytics'), value: '' },
                { label: '7d', value: '7d' },
                { label: '14d', value: '14d' },
                { label: '30d', value: '30d' },
              ],
              onChange: (v) => setAttributes({ range: v }),
            }),
            wp.element.createElement(TextControl, {
              label: __('Domain override (optional)', 'wp-cabin-analytics'),
              help: __('Leave blank to use this site’s domain.', 'wp-cabin-analytics'),
              value: attributes.domain || '',
              onChange: (v) => setAttributes({ domain: v }),
            }),
            wp.element.createElement(ToggleControl, {
              label: __('Show footer', 'wp-cabin-analytics'),
              checked: !!attributes.showFooter,
              onChange: (v) => setAttributes({ showFooter: !!v }),
            }),
          )
        ),
        wp.element.createElement(ServerSideRender, {
          key: 'ssr',
          block: 'wp-cabin/analytics',
          attributes
        })
      ];
    },
    save: () => null,
  });
} )( window.wp );
