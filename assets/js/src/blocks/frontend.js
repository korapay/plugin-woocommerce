import { createElement } from '@wordpress/element';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { getSetting } from '@woocommerce/settings';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';

const settings = getSetting('korapay_data', {});
const defaultLabel = __('Korapay ', 'woo-korapay');
const supportedCurrencies = settings.supported_currencies || [];

const Content = ({ description }) => decodeEntities(description || '');

const Label = ({ title }) => createElement(
    'span',
    { style: { fontWeight: 'inherit' } },
    decodeEntities(title) || defaultLabel
);

const canMakePayment = ({ cartTotals }) =>
    supportedCurrencies.includes(cartTotals.currency_code);

registerPaymentMethod({
    name: 'korapay',
    label: createElement(Label, { title: settings.title }),
    content: createElement(Content, { description: settings.description }),
    edit: createElement(Content, { description: settings.description }),
    canMakePayment,
    ariaLabel: decodeEntities(settings.title) || defaultLabel,
    supports: {
        features: settings.supports,
    },
});
