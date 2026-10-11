/* Die Bausteine des Themes im Editor: Vorschau vom Server, keine eigenen Einstellungen. */
(function (wp) {
    'use strict';
    var el = wp.element.createElement;
    var ServerSideRender = wp.serverSideRender;
    var useBlockProps = wp.blockEditor.useBlockProps;

    var NAMEN = {
        kicker: 'Überzeile (Art · Datum)',
        eckdaten: 'Eckdaten (km · Hm · Lesezeit)',
        vorspann: 'Vorspann (eigener Auszug)',
        steckbrief: 'Steckbrief',
        uebersicht: 'Alle Berichte (Übersicht)',
        galerie: 'Alle Fotos und Videos (Galerie)'
    };

    Object.keys(NAMEN).forEach(function (name) {
        wp.blocks.registerBlockType('radional/' + name, {
            apiVersion: 3,
            edit: function (props) {
                return el('div', useBlockProps(),
                    el(ServerSideRender, {
                        block: 'radional/' + name,
                        attributes: props.attributes,
                        urlQueryArgs: props.context && props.context.postId ? { post_id: props.context.postId } : {},
                        EmptyResponsePlaceholder: function () {
                            return el('p', { style: { opacity: 0.6, fontSize: '13px' } },
                                NAMEN[name] + ' – erscheint, sobald der Beitrag Werte hat.');
                        }
                    })
                );
            },
            save: function () { return null; }
        });
    });
})(window.wp);
