from flask import Flask, request, jsonify
from flask_cors import CORS
from openai import OpenAI
import os
from dotenv import load_dotenv

# Load environment variables from .env file
load_dotenv()

app = Flask(__name__)
CORS(app)  # This allows frontend to communicate with backend

# Initialize OpenAI client with your API key
client = OpenAI(api_key=os.getenv('OPENAI_API_KEY'))

# Store conversation history (in production, use a database)
conversation_history = {}

# System prompt that defines how the AI should behave
SYSTEM_PROMPT = {
    "role": "system", 
    "content": """You are 'StudyBuddy', a friendly, patient, and enthusiastic AI study assistant for students of all levels. Your primary goal is to foster deep understanding, not just provide answers.

Guidelines:
1. Be Encouraging: Always start with a positive tone.
2. Adapt to Level: Gauge the user's knowledge level and adjust your explanation complexity.
3. Explain Concepts: Focus on the 'why' behind an answer. Use analogies and real-world examples.
4. Show Your Work: For technical problems, provide step-by-step breakdowns.
5. Be Concise but Thorough: Avoid unnecessary fluff, but ensure core concepts are fully explained.
6. Stay Within Scope: Focus on educational, engineering, programming, and career advice.
7. Use Markdown: Format your responses with headings, bullet points, and code blocks when appropriate."""
}

@app.route('/chat', methods=['POST'])
def chat():
    try:
        data = request.json
        user_message = data.get('message')
        user_id = data.get('user_id', 'default_user')  # Simple user tracking
        
        print(f"Received message from {user_id}: {user_message}")
        
        # Initialize or retrieve conversation history for this user
        if user_id not in conversation_history:
            conversation_history[user_id] = [SYSTEM_PROMPT]
        
        # Add user's message to history
        conversation_history[user_id].append({"role": "user", "content": user_message})
        
        # Call OpenAI API
        response = client.chat.completions.create(
            model="gpt-3.5-turbo",  # You can use "gpt-4" if you have access
            messages=conversation_history[user_id],
            max_tokens=1000,
            temperature=0.7
        )
        
        # Get AI's reply
        ai_reply = response.choices[0].message.content
        
        # Add AI's reply to history
        conversation_history[user_id].append({"role": "assistant", "content": ai_reply})
        
        print(f"Sent response to {user_id}")
        return jsonify({"reply": ai_reply, "status": "success"})
        
    except Exception as e:
        print(f"Error: {str(e)}")
        return jsonify({"error": "Network error. Please try again.", "status": "error"}), 500

@app.route('/health', methods=['GET'])
def health_check():
    return jsonify({"status": "Backend is running!"})

if __name__ == '__main__':
    app.run(debug=True, port=5000)