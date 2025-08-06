const { join, resolve } = require('path');

module.exports = () => {
    return {
        resolve: {
            alias: {
                '@LineItemComments': resolve(
                    join(__dirname, '..', 'app', 'storefront', 'src')
                )
            }
        }
    };
};