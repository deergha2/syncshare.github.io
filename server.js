const express = require('express');
const mysql = require('mysql');
const bcrypt = require('bcrypt');
const session = require('express-session');
const bodyParser = require('body-parser');
const csrf = require('csurf');
const { body, validationResult } = require('express-validator');

const app = express();
const port = 3000;

// Database connection
const connection = mysql.createConnection({
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'syncshare'
});

connection.connect(err => {
    if (err) throw err;
    console.log('Connected to database.');
});

// Middleware
app.use(bodyParser.urlencoded({ extended: true }));
app.use(express.static('public')); // For serving static files like HTML
app.use(session({
    secret: process.env.SESSION_SECRET || 'your_secret_key',
    resave: false,
    saveUninitialized: false,
    cookie: {
        secure: process.env.NODE_ENV === 'production', // Set secure cookies in production
        httpOnly: true,
        maxAge: 24 * 60 * 60 * 1000 // 24 hours
    }
}));

// CSRF Protection
const csrfProtection = csrf({ cookie: true });
app.use(csrfProtection);

// Serve HTML pages
app.get('/', (req, res) => {
    res.sendFile(__dirname + 'index.html');
});

app.get('/register', (req, res) => {
    res.sendFile(__dirname + 'login.html');
});

app.get('/login', (req, res) => {
    res.sendFile(__dirname + 'login.html');
});

// Registration route with validation and CSRF protection
app.post('/register', [
    body('username').isLength({ min: 3 }).withMessage('Username must be at least 3 characters long'),
    body('email').isEmail().withMessage('Invalid email address'),
    body('password').isLength({ min: 6 }).withMessage('Password must be at least 6 characters long')
], csrfProtection, async (req, res) => {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
        return res.status(400).json({ errors: errors.array() });
    }

    const { username, email, password } = req.body;
    try {
        const hashedPassword = await bcrypt.hash(password, 10);
        connection.query('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)', 
            [username, email, hashedPassword], 
            (err, results) => {
                if (err) {
                    console.error(err);
                    return res.status(500).send('Registration failed.');
                }
                res.send('Registration successful!');
            });
    } catch (err) {
        console.error(err);
        res.status(500).send('Error during registration.');
    }
});

// Login route with CSRF protection
app.post('/login', csrfProtection, (req, res) => {
    const { email, password } = req.body;

    connection.query('SELECT * FROM users WHERE email = ?', [email], async (err, results) => {
        if (err) {
            console.error(err);
            return res.status(500).send('Login failed.');
        }
        
        if (results.length > 0) {
            const user = results[0];
            const match = await bcrypt.compare(password, user.password_hash);

            if (match) {
                req.session.userId = user.id;
                res.send('Login successful!');
            } else {
                res.send('Invalid credentials.');
            }
        } else {
            res.send('User not found.');
        }
    });
});

// Logout route
app.get('/logout', (req, res) => {
    req.session.destroy(err => {
        if (err) {
            console.error(err);
            return res.status(500).send('Logout failed.');
        }
        res.send('Logged out successfully.');
    });
});

// Start server
app.listen(port, () => {
    console.log(`Server running on http://localhost:${port}`);
});
