(() => {
    "use strict";
    const e = window.wp.element,
          t = window.wp.i18n,
          n = window.wc.wcBlocksRegistry,
          i = window.wp.htmlEntities;

          const paymentMethods = [
            {
                name: "paylabs_h5",
                defaultTitle: "Paylabs H5",
                defaultDescription: "Paylabs All Payment",
            },
            {
                name: "paylabs_maybank_va",
                defaultTitle: "Maybank VA",
                defaultDescription: "Paylabs Maybank VA",
            },
            {
                name: "paylabs_danamon_va",
                defaultTitle: "Danamon VA",
                defaultDescription: "Paylabs Danamon VA",
            },
            {
                name: "paylabs_sinarmas_va",
                defaultTitle: "Sinarmas VA",
                defaultDescription: "Paylabs Sinarmas VA",
            },
            {
                name: "paylabs_bsi_va",
                defaultTitle: "BSI VA",
                defaultDescription: "Paylabs BSI VA",
            },
            {
                name: "paylabs_bnc_va",
                defaultTitle: "BNC VA",
                defaultDescription: "Paylabs BNC VA",
            },
            {
                name: "paylabs_ina_va",
                defaultTitle: "INA VA",
                defaultDescription: "Paylabs INA VA",
            },
            {
                name: "paylabs_bni_va",
                defaultTitle: "BNI VA",
                defaultDescription: "Paylabs BNI VA",
            },
            {
                name: "paylabs_mandiri_va",
                defaultTitle: "Mandiri VA",
                defaultDescription: "Paylabs Mandiri VA",
            },
            {
                name: "paylabs_permata_va",
                defaultTitle: "Permata VA",
                defaultDescription: "Paylabs Permata VA",
            },
            {
                name: "paylabs_btn_va",
                defaultTitle: "BTN VA",
                defaultDescription: "Paylabs BTN VA",
            },
            {
                name: "paylabs_cimb_va",
                defaultTitle: "CIMB VA",
                defaultDescription: "Paylabs CIMB VA",
            },
            {
                name: "paylabs_muamalat_va",
                defaultTitle: "Muamalat VA",
                defaultDescription: "Paylabs Muamalat VA",
            },
            {
                name: "paylabs_bca_va",
                defaultTitle: "BCA VA",
                defaultDescription: "Paylabs BCA VA",
            },
            {
                name: "paylabs_bri_va",
                defaultTitle: "BRI VA",
                defaultDescription: "Paylabs BRI VA",
            },
            {
                name: "paylabs_qris",
                defaultTitle: "QRIS",
                defaultDescription: "Paylabs QRIS Payment",
            },
            {
                name: "paylabs_dana_balance",
                defaultTitle: "Dana Balance",
                defaultDescription: "Paylabs Dana Balance",
            },
            {
                name: "paylabs_ovo_balance",
                defaultTitle: "OVO Balance",
                defaultDescription: "Paylabs OVO Balance",
            },
            {
                name: "paylabs_linkaja_balance",
                defaultTitle: "LinkAja Balance",
                defaultDescription: "Paylabs LinkAja Balance",
            },
            {
                name: "paylabs_shopee_balance",
                defaultTitle: "Shopee Balance",
                defaultDescription: "Paylabs Shopee Balance",
            },
            {
                name: "paylabs_gopay_balance",
                defaultTitle: "GoPay Balance",
                defaultDescription: "Paylabs GoPay Balance",
            }
        ];
        

    paymentMethods.forEach(method => {
        const o = paylabsPaymentMethods[method.name] || {},
              s = (0, t.__)(o.title || method.defaultTitle, "woo-gutenberg-products-block"),
              a = (0, i.decodeEntities)(o.title) || s,
              c = () => (0, i.decodeEntities)(o.description || method.defaultDescription),
              l = {
                  name: method.name,
                  label: (0, e.createElement)((t => {
                      const { PaymentMethodLabel: n } = t.components;
                      return (0, e.createElement)(n, { text: a })
                  }), null),
                  content: (0, e.createElement)(c, null),
                  edit: (0, e.createElement)(c, null),
                  canMakePayment: () => !0,
                  ariaLabel: a,
                  supports: {
                      features: o.supports
                  }
              };
        (0, n.registerPaymentMethod)(l);
    });
})();
