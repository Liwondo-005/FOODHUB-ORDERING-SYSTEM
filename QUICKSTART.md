# FoodHub - Quick Start Guide

## 📋 Prerequisites Checklist

- ✅ XAMPP installed with MySQL and Apache running
- ✅ FoodHub folder in `C:\xampp\htdocs\FOOD\`
- ✅ Database imported (`foodhub_schema.sql`)
- ✅ PHP 8.2+ available

## 🚀 Quick Setup (5 minutes)

### Step 1: Import Database
```bash
# Via phpMyAdmin
1. Go to http://localhost/phpmyadmin
2. Create database: foodhub_db
3. Click Import → Select foodhub_schema.sql → Go

# OR via MySQL CLI
mysql -u root -p < foodhub_schema.sql
```

### Step 2: Start Application
```
Open: http://localhost/FOOD/frontend/app.html
```

## 🎯 Complete Order Flow Test

### 1️⃣ Customer Registration
```
1. Click "Join Us" button
2. Fill form:
   - Name: Test Customer
   - Email: testcust@example.com
   - Password: password123
   - Account Type: Customer
3. Click "Register"
→ Redirected to login page
```

### 2️⃣ Customer Login
```
1. Email: testcust@example.com
2. Password: password123
3. Click "Login"
→ Redirected to restaurants page
```

### 3️⃣ Browse Restaurants
```
1. Click "Restaurants Near You" or see list
2. Try search:
   - Search: "burger"
   - Area: "Westlands"
→ See Burger King restaurant appear
```

### 4️⃣ View Menu
```
1. Click "View Menu" on Burger King
2. Modal opens with menu items:
   - Whopper Burger (450)
   - Cheeseburger (350)
   - French Fries (150)
3. Select quantities and click "Add"
→ Items added to cart
```

### 5️⃣ Add to Cart
```
1. Whopper: 2 qty → Add
2. French Fries: 1 qty → Add
→ Toast: "Added to cart!"
```

### 6️⃣ Review Cart
```
1. Click "Cart" in navigation
2. See items:
   - Whopper x2 = 900
   - French Fries x1 = 150
   - Total: 1050
3. Click "Proceed to Checkout"
```

### 7️⃣ Checkout
```
1. Fill form:
   - Address: 123 Main St, Nairobi
   - Phone: +254712345678
   - Payment: Cash on Delivery
2. Optional special instructions
3. Click "Place Order"
→ Success: Order #X created
```

### 8️⃣ Confirm Order in Database
```
1. Open phpMyAdmin
2. Go to foodhub_db → orders table
3. See new order with:
   - user_id: Your ID
   - restaurant_id: 1 (Burger King)
   - total_amount: 1050
   - status: pending
4. Check order_items table:
   - Whopper (menu_item_id:1) x2
   - French Fries (menu_item_id:3) x1
```

### 9️⃣ Track Order
```
1. Click "My Orders"
2. See order with status "pending"
3. Click "View Details"
→ Itemized breakdown displayed
```

### 🔟 Owner Dashboard (Update Status)
```
1. Logout from customer account
2. Register or login as owner:
   - Email: john@burgers.com
   - Password: password
3. Automatically redirected to Owner Dashboard
4. See incoming order from customer
5. Change status dropdown:
   - pending → preparing (click)
   - See status update
   - Change to ready
   - Change to delivered
6. Refresh customer orders:
   → Status updated in real-time
```

## 🔐 Test Credentials

### Customers
```
alice@example.com / password
bob@example.com / password
testcust@example.com / password (registered in steps above)
```

### Restaurant Owners
```
john@burgers.com / password
mary@kitchen.com / password
```

### Admin
```
admin@foodhub.com / password
```

## 📊 Database Verification

### Check Order Creation
```sql
-- View all orders
SELECT o.id, u.name as customer, r.name as restaurant, o.total_amount, o.status
FROM orders o
JOIN users u ON o.user_id = u.id
JOIN restaurants r ON o.restaurant_id = r.id;

-- View order items
SELECT oi.id, m.name, oi.quantity, oi.price
FROM order_items oi
JOIN menu_items m ON oi.menu_item_id = m.id
WHERE oi.order_id = 1;
```

### Verify Cart and Payments
```sql
-- Check carts
SELECT * FROM carts;

-- Check payments
SELECT p.id, p.order_id, p.method, p.amount, p.status
FROM payments p;
```

## 🛠️ Troubleshooting

### Issue: "DB connection failed"
```
✓ Check XAMPP MySQL is running
✓ Verify credentials in backend/config/db.php
✓ Check database name: foodhub_db exists
```

### Issue: Blank page on http://localhost/FOOD/frontend/app.html
```
✓ Verify XAMPP Apache is running
✓ Check folder: C:\xampp\htdocs\FOOD\
✓ Verify app.html exists
✓ Check browser console for errors (F12)
```

### Issue: Login fails
```
✓ Verify credentials match sample data
✓ Check users table in database
✓ Ensure user status is 'active'
```

### Issue: Cart not saving
```
✓ Check browser cookies enabled
✓ Verify PHP sessions enabled
✓ Check carts table in database
✓ Verify user_id matches session
```

### Issue: Order not appearing
```
✓ Verify cart had items before checkout
✓ Check orders table for new entry
✓ Verify order_items count matches
✓ Check MySQL error logs
```

## 📁 File Structure Reference

```
C:\xampp\htdocs\FOOD\
├── backend/
│   ├── config/db.php ..................... Database connection
│   ├── auth/ ............................ Login/Register endpoints
│   ├── restaurants/list.php ............. Restaurant search
│   ├── restaurants/menu.php ............. Menu items
│   ├── cart/cart.php .................... Cart operations
│   ├── orders/customer_orders.php ....... Order placement/tracking
│   ├── owner/orders.php ................. Owner order management
│   └── admin/users.php .................. Admin interface
│
├── frontend/
│   ├── app.html ......................... Main SPA (OPEN THIS!)
│   ├── js/app.js ........................ Complete application logic
│   └── js/main.js ....................... Initial JS file
│
├── foodhub_schema.sql ................... Database schema (IMPORT THIS!)
├── README.md ............................ Full documentation
└── PROJECT_REPORT.md .................... Detailed technical report
```

## ⚡ API Endpoints Quick Reference

```
Authentication:
├─ POST   /backend/auth/register.php
├─ POST   /backend/auth/login.php
├─ POST   /backend/auth/logout.php
└─ GET    /backend/auth/user.php

Restaurants:
├─ GET    /backend/restaurants/list.php?search=X&area=Y
└─ GET    /backend/restaurants/menu.php?restaurant_id=X

Cart:
├─ GET    /backend/cart/cart.php
├─ POST   /backend/cart/cart.php
├─ PUT    /backend/cart/cart.php
└─ DELETE /backend/cart/cart.php

Orders:
├─ GET    /backend/orders/customer_orders.php
├─ GET    /backend/orders/customer_orders.php?id=X
└─ POST   /backend/orders/customer_orders.php

Owner:
├─ GET    /backend/owner/orders.php
└─ PUT    /backend/owner/orders.php

Admin:
└─ GET    /backend/admin/users.php
```

## ✨ Key Features Demonstrated

- ✅ User Registration with Password Hashing
- ✅ Role-Based Access Control (Customer/Owner/Admin)
- ✅ Restaurant Search & Filtering
- ✅ Menu Browsing
- ✅ Shopping Cart Management
- ✅ Order Placement with Validation
- ✅ Real-Time Order Status Updates
- ✅ Complete Order History Tracking
- ✅ Owner Dashboard for Order Management
- ✅ Database Normalization (3NF)
- ✅ Prepared Statements (SQL Injection Prevention)
- ✅ Session Management
- ✅ Fetch API Integration
- ✅ Responsive Design

## 📸 Screenshots (Recommended Tests)

1. Home Page - Hero section
2. Restaurant Listing - Search results
3. Menu Modal - Item selection
4. Cart Summary - Before checkout
5. Order Confirmation - Success message
6. Order Tracking - History and status
7. Owner Dashboard - Order management
8. Database Tables - Verify data persistence

## 🎓 Learning Outcomes

Students completing this project demonstrate:
- ✅ Full-stack web development skills
- ✅ Database design and normalization (3NF)
- ✅ Secure backend development (hashing, prepared statements)
- ✅ RESTful API design
- ✅ Vanilla JavaScript (no frameworks)
- ✅ Fetch API for async communication
- ✅ Responsive web design
- ✅ Version control preparation (for GitHub)
- ✅ Technical documentation
- ✅ Project deployment knowledge

## ❓ FAQ

**Q: How do I reset the database?**
A: Drop the database and re-import foodhub_schema.sql

**Q: Can I add more restaurants?**
A: Yes, insert directly into `restaurants` table:
```sql
INSERT INTO restaurants (name, description, cuisine, area, owner_id, status)
VALUES ('New Restaurant', 'Description', 'Cuisine', 'Area', 2, 'active');
```

**Q: How do I add menu items?**
A: Insert into `menu_items` table with restaurant_id and category_id

**Q: Is HTTPS required?**
A: Not for local (localhost), required for production deployment

**Q: How long does deployment take?**
A: ~30-60 minutes to AWS with proper configuration

**Q: What about mobile app?**
A: Same APIs work with React Native or Flutter frontend

---

**Last Updated:** February 8, 2026
**Status:** ✅ Production Ready
**Version:** 1.0.0
