from ..database import db

class Order(db.Model):
    __tablename__ = 'order'
    OrderID = db.Column(db.Integer, primary_key=True)
    UserID = db.Column(db.Integer, db.ForeignKey('user.UserID', ondelete='CASCADE'))
    OrderDate = db.Column(db.DateTime, server_default=db.func.current_timestamp())
    TotalAmount = db.Column(db.Numeric(10, 2))
    ShippingAddress = db.Column(db.Text)
    OrderStatus = db.Column(db.Enum('Pending', 'Shipped', 'Delivered', 'Cancelled'), default='Pending')

class OrderItem(db.Model):
    __tablename__ = 'orderitem'
    OrderItemID = db.Column(db.Integer, primary_key=True)
    OrderID = db.Column(db.Integer, db.ForeignKey('order.OrderID', ondelete='CASCADE'))
    BookID = db.Column(db.Integer, db.ForeignKey('book.BookID'))
    Quantity = db.Column(db.Integer, nullable=False)
    UnitPrice = db.Column(db.Numeric(10, 2), nullable=False)

class Payment(db.Model):
    __tablename__ = 'payment'
    PaymentID = db.Column(db.Integer, primary_key=True)
    OrderID = db.Column(db.Integer, db.ForeignKey('order.OrderID'))
    PaymentMethod = db.Column(db.String(50))
    TransactionID = db.Column(db.String(100), unique=True)
    PaymentStatus = db.Column(db.Enum('Success', 'Failed', 'Pending'))
    PaymentDate = db.Column(db.DateTime)
    Amount = db.Column(db.Numeric(10, 2))

class DigitalDownload(db.Model):
    __tablename__ = 'digitaldownload'
    DownloadID = db.Column(db.Integer, primary_key=True)
    OrderItemID = db.Column(db.Integer, db.ForeignKey('orderitem.OrderItemID', ondelete='CASCADE'))
    DownloadLink = db.Column(db.String(255))
    ExpiryDate = db.Column(db.DateTime)
    AccessCount = db.Column(db.Integer, default=0)
