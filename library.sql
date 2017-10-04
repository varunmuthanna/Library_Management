CREATE TABLE book (
	isbn CHAR(10) NOT NULL,
	isbn13 CHAR(13),
	title VARCHAR(300),
	cover VARCHAR(2083),
	publisher VARCHAR(100),
	pages INT,
	CONSTRAINT pk_book PRIMARY KEY (isbn)
);

CREATE TABLE authors (
	author_id INT NOT NULL AUTO_INCREMENT,
	fullname VARCHAR(60),
	CONSTRAINT pk_authors PRIMARY KEY (author_id)
);

CREATE TABLE book_authors (
	isbn CHAR(10) NOT NULL,
	author_id INT NOT NULL,
	CONSTRAINT pk_book_authors PRIMARY KEY (isbn, author_id),
	CONSTRAINT fk_aurthor FOREIGN KEY (author_id) REFERENCES authors(author_id) ON DELETE CASCADE,
	CONSTRAINT fk_book FOREIGN KEY (isbn) REFERENCES book(isbn)
);

CREATE TABLE borrower (
	card_no CHAR(8) NOT NULL,
	ssn CHAR(11) NOT NULL UNIQUE,
	Fname VARCHAR(45) NOT NULL,
	Lname VARCHAR(45) NOT NULL,
	email VARCHAR(255),
	address VARCHAR(100) NOT NULL,
	city VARCHAR(30) NOT NULL,
	state VARCHAR(30) NOT NULL,
	phone CHAR(14),
	CONSTRAINT pk_borrower PRIMARY KEY (card_no)
);

CREATE TABLE book_loans (
	loan_id INT NOT NULL AUTO_INCREMENT,
	isbn CHAR(10) NOT NULL,
	card_no CHAR(8) NOT NULL,
	date_out DATE NOT NULL,
	due_date DATE NOT NULL,
	date_in DATE,
	CONSTRAINT pk_book_loans PRIMARY KEY (loan_id),
	CONSTRAINT fk_book_loans_borrower FOREIGN KEY (card_no) REFERENCES borrower(card_no)
);

CREATE TABLE fines (
	loan_id INT NOT NULL,
	fine_amt FLOAT(7,2),
	paid TINYINT,
	CONSTRAINT pk_fine PRIMARY KEY (loan_id),
	CONSTRAINT fk_fine_loan FOREIGN KEY (loan_id) REFERENCES book_loans(loan_id)
);
