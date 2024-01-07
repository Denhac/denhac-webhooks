const express = require('express');
const { LocalStorage } = require('node-localstorage')

const app = express();

const port = 80;
const storagePath = 'storage/';

const storage = new LocalStorage(storagePath);

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
    res.send('woocommerce_rest_wc_user_membership_exists');
})


app.listen(port, () => {
    console.log(`Listening on port ${port}`);
})
