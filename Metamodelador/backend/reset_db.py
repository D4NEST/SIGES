# reset_db.py
from app import create_app
from database import db

app = create_app()
with app.app_context():
    db.drop_all()
    print("✅ Todas las tablas eliminadas")
    db.create_all()
    print("✅ Tablas recreadas con la nueva estructura")