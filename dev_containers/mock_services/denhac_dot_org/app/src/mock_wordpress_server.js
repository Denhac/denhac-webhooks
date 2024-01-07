const express = require('express');
const { LocalStorage } = require('node-localstorage')
const crypto = require('crypto');

const app = express();

const port = 80;
const storagePath = 'storage/';

const storage = new LocalStorage(storagePath);

// Utils for sending messages to the webhook server
const webhookTarget = 'http://web/webhooks/denhac-org';
const outboundSigningSecret = process.env.OUTBOUND_SIGNING_SECRET;
const signWebhook = (content) => crypto.createHmac('sha256', outboundSigningSecret).update(JSON.stringify(content)).digest('base64');

// Reference app/External/WooCommerce/WebhookCall.php
const sendWebhook = (topic, message) => {
    console.log('Sending message to webhook server. Topic:', topic);
    return fetch(webhookTarget, {
        method: 'POST',
        body: JSON.stringify(message),
        headers: {
            'X-WC-Webhook-Signature': signWebhook(message),
            'X-WC-Webhook-Topic': topic,
            'Content-Type': 'application/json',
        }
    }).then(response => console.log(`[${response.status}] Received webhook response`))
};

// MIDDLEWARE
// Request Logger
app.use((req, _res, next) => {
    console.log(req.method, req.originalUrl);
    next();
});
// Parse JSON bodies
app.use(express.json());


// ROUTES
app.post('/wp-json/wc-denhac/v1/user_plans', (req, res) => {
    const { author, title } = req.body;

    const plans = JSON.parse(storage.getItem('userPlans') || '[]')

    const newPlan = { id: plans.length, author, title };
    plans.push(newPlan);

    storage.setItem('userPlans', JSON.stringify(plans));

    res.json(newPlan);
});

app.post('/wp-json/wc/v3/memberships/members', (req, res) => {
    const { customer_id, plan_id } = req.body;
    sendWebhook('user_membership.created', { customer_id, plan_id }).then(() =>
        res.send('woocommerce_rest_wc_user_membership_exists')
    ).catch(() => res.status(500).send('Something went wrong.'));
})


app.listen(port, () => {
    console.log(`Listening on port ${port}`);
});
