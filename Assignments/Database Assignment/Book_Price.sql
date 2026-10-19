
-- EE 470 Database Assignment - Part 1
-- Section 1.2: Book and Price Tables
-- Rachel Markman

-- Create Book table
CREATE TABLE Book (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    PRIMARY KEY (id)
);

-- Create Price table
CREATE TABLE Price (
    id INT NOT NULL AUTO_INCREMENT,
    price FLOAT NOT NULL,
    PRIMARY KEY (id)
);

-- Insert five books
INSERT INTO Book (id, name)
VALUES
(1, 'MariaDB Book1'),
(2, 'MariaDB Book2'),
(3, 'MariaDB Book3'),
(4, 'MariaDB Book4'),
(5, 'MariaDB Book5');

-- Insert corresponding prices
INSERT INTO Price (id, price)
VALUES
(1, 251),
(2, 252),
(3, 223),
(4, 194),
(5, 305);

-- Update first book name
UPDATE Book
SET name = 'MariaDB Book1a'
WHERE id = 1;

-- Update first book price
UPDATE Price
SET price = 250
WHERE id = 1;

-- Display Book table
SELECT * FROM Book;

-- Display Price table
SELECT * FROM Price;

-- Display prices below 250
SELECT * FROM Price
WHERE price < 250;

-- Display records matching conditions
SELECT * FROM Price
WHERE id > 3 OR price < 250;

-- Find book names ending in 4
SELECT name FROM Book
WHERE name LIKE '%4';

-- Find prices starting with 2
SELECT * FROM Price
WHERE price LIKE '2%'
ORDER BY price;

-- Combine Book and Price tables
SELECT Book.name, Price.price
FROM Book
INNER JOIN Price
ON Book.id = Price.id;
