#!/usr/bin/env python3
"""
Multi-Tenant Frontend Server
Routes all business URLs to the same HTML file without requiring folders.

Usage:
    python server.py [port]
    
Example:
    python server.py 8000
    
Then visit:
    http://localhost:8000/acme-driving/
    http://localhost:8000/city-school/
    http://localhost:8000/any-business-slug/
"""

import http.server
import socketserver
import os
import sys
import mimetypes
from urllib.parse import urlparse, unquote

PORT = int(sys.argv[1]) if len(sys.argv) > 1 else 8000

class MultiTenantHandler(http.server.SimpleHTTPRequestHandler):
    """
    Custom handler that routes all business URLs to the same HTML file.
    """
    
    # Files that can be served directly
    ALLOWED_FILES = {
        'driving_school_app.html',
        'index.html',
        'portal.html',
        'test-detection.html',
        'API_QUICK_REFERENCE.md',
        'URL_ROUTING_GUIDE.md',
        'TROUBLESHOOTING.md',
        'SETUP_COMPLETE.md'
    }
    
    def do_GET(self):
        """Handle GET requests with multi-tenant routing."""
        
        # Parse the URL
        parsed_path = urlparse(self.path)
        path = unquote(parsed_path.path)
        
        # Remove leading/trailing slashes and split
        path_parts = [p for p in path.split('/') if p]
        
        # Determine what to serve
        file_to_serve = None
        
        if not path_parts or path == '/':
            # Root URL - serve index.html
            file_to_serve = 'index.html'
        
        elif len(path_parts) == 1:
            first_part = path_parts[0]
            
            # Check if it's a direct file request
            if first_part in self.ALLOWED_FILES:
                file_to_serve = first_part
            
            # Check if file exists in filesystem
            elif os.path.isfile(first_part):
                file_to_serve = first_part
            
            # Otherwise, assume it's a business slug - serve the app
            else:
                # Pattern: /business-slug/ -> serve driving_school_app.html
                file_to_serve = 'driving_school_app.html'
        
        elif len(path_parts) >= 2:
            # Pattern: /business-slug/file.html
            # First part is business slug, second part is file
            
            last_part = path_parts[-1]
            
            # Check if last part is a file we can serve
            if last_part in self.ALLOWED_FILES or os.path.isfile(last_part):
                file_to_serve = last_part
            else:
                # Default to driving_school_app.html
                file_to_serve = 'driving_school_app.html'
        
        # Serve the file
        if file_to_serve and os.path.isfile(file_to_serve):
            self.serve_file(file_to_serve)
        else:
            self.send_error(404, f"File not found: {path}")
    
    def serve_file(self, filename):
        """Serve a specific file with correct content type."""
        try:
            # Get content type
            content_type, _ = mimetypes.guess_type(filename)
            if content_type is None:
                content_type = 'application/octet-stream'
            
            # Read and serve the file
            with open(filename, 'rb') as f:
                content = f.read()
                
            self.send_response(200)
            self.send_header('Content-Type', content_type)
            self.send_header('Content-Length', len(content))
            self.send_header('Cache-Control', 'no-store, no-cache, must-revalidate')
            self.end_headers()
            self.wfile.write(content)
            
        except Exception as e:
            self.send_error(500, f"Error serving file: {str(e)}")
    
    def log_message(self, format, *args):
        """Custom log format to show routing."""
        path = args[0].split()[0] if args else ''
        status = args[1] if len(args) > 1 else ''
        
        # Color coding for status
        if status.startswith('2'):
            status_color = '\033[92m'  # Green
        elif status.startswith('3'):
            status_color = '\033[93m'  # Yellow
        elif status.startswith('4') or status.startswith('5'):
            status_color = '\033[91m'  # Red
        else:
            status_color = '\033[0m'   # Default
        
        print(f"{status_color}{status}\033[0m {self.command} {path}")

def run_server():
    """Start the multi-tenant server."""
    
    # Change to frontend directory if needed
    if os.path.basename(os.getcwd()) != 'frontend':
        if os.path.isdir('frontend'):
            os.chdir('frontend')
    
    # Create server
    with socketserver.TCPServer(("", PORT), MultiTenantHandler) as httpd:
        print("=" * 70)
        print(f"🚗 DriveScheduler Multi-Tenant Frontend Server")
        print("=" * 70)
        print(f"\n📍 Server running at: http://localhost:{PORT}/\n")
        print("🎯 Example URLs:")
        print(f"   • Landing page:      http://localhost:{PORT}/")
        print(f"   • Acme Driving:      http://localhost:{PORT}/acme-driving/")
        print(f"   • City School:       http://localhost:{PORT}/city-school/")
        print(f"   • Any business:      http://localhost:{PORT}/your-business-name/")
        print(f"   • Demo mode:         http://localhost:{PORT}/driving_school_app.html")
        print(f"   • Test detection:    http://localhost:{PORT}/test-detection.html")
        print("\n💡 The same HTML file serves ALL businesses - no folders needed!")
        print("\n🔧 Business detection happens in JavaScript from the URL path.")
        print(f"\n⏹️  Press Ctrl+C to stop\n")
        print("=" * 70)
        print()
        
        try:
            httpd.serve_forever()
        except KeyboardInterrupt:
            print("\n\n🛑 Server stopped")
            sys.exit(0)

if __name__ == "__main__":
    run_server()

