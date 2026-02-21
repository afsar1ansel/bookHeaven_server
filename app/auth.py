from functools import wraps
from flask import request, jsonify, current_app
import jwt

def token_required(f):
    @wraps(f)
    def decorated(*args, **kwargs):
        auth_header = request.headers.get('Authorization')
        if not auth_header:
            return jsonify({"error": "Token missing"}), 401
        
        try:
            if not auth_header.startswith("Bearer "):
                return jsonify({"error": "Invalid token format. Use Bearer <token>"}), 401
            
            token = auth_header.split(" ")[1]
            payload = jwt.decode(token, current_app.config['SECRET_KEY'], algorithms=['HS256'])
            # Attach payload to request for use in routes if needed
            request.user_payload = payload
            return f(*args, **kwargs)
        except jwt.ExpiredSignatureError:
            return jsonify({"error": "Token has expired"}), 401
        except jwt.InvalidTokenError as e:
            return jsonify({"error": f"Invalid token: {str(e)}"}), 401
        except Exception as e:
            return jsonify({"error": f"Authorization error: {str(e)}"}), 401
            
    return decorated

def admin_required(f):
    @wraps(f)
    def decorated(*args, **kwargs):
        auth_header = request.headers.get('Authorization')
        if not auth_header:
            return jsonify({"error": "Admin token missing"}), 401
        
        try:
            if not auth_header.startswith("Bearer "):
                return jsonify({"error": "Invalid token format. Use Bearer <token>"}), 401
            
            token = auth_header.split(" ")[1]
            payload = jwt.decode(token, current_app.config['SECRET_KEY'], algorithms=['HS256'])
            
            if payload.get('role') != 'admin':
                return jsonify({"error": "Admin access required"}), 403
                
            request.user_payload = payload
            return f(*args, **kwargs)
        except jwt.ExpiredSignatureError:
            return jsonify({"error": "Token has expired"}), 401
        except jwt.InvalidTokenError as e:
            return jsonify({"error": f"Invalid token: {str(e)}"}), 401
        except Exception as e:
            return jsonify({"error": f"Authorization error: {str(e)}"}), 401
            
    return decorated
