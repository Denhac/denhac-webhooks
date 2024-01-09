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

const addRecord = (storeName, data) => {
    const records = JSON.parse(storage.getItem(storeName) || '[]')

    const newRecord = { ...data, id: records.length };
    records.push(newRecord);

    storage.setItem(storeName, JSON.stringify(records));
    return newRecord;
}

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

/**
 * Create a UserPlan object, representing a "permission", etc. that can be associated with many members.
 * Expects JSON body with properties:
 *   author: int - Customer.id
 *   title: string
 * Returns: JSON body with input properties plus:
 *    id: int - UserPlan.id
 */
app.post('/wp-json/wc-denhac/v1/user_plans', (req, res) => {
    const { author, title } = req.body;
    res.json(addRecord('userPlans', { author, title }));
});


/**
 * Create a UserMembership, which is an association of a UserPlan and a Customer
 * Expects JSON body with properties:
 *   customer_id: int - Customer.id 
 *   plan_Id: int - UserPlan.id 
 * Returns: JSON body with input properties plus:
 *    id: int - UserMembership.id
 *    status: enum[string] - active|...
 */
app.post('/wp-json/wc/v3/memberships/members', (req, res) => {
    const { customer_id, plan_id } = req.body;
    sendWebhook('user_membership.created', addRecord('userMemberships', { customer_id, plan_id, status: 'active' })).then(() =>
        res.send('woocommerce_rest_wc_user_membership_exists')
    ).catch(() => res.status(500).send('Something went wrong.'));
})

app.listen(port, () => {
    console.log(`Listening on port ${port}`);
});
