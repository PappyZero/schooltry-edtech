# SchoolTry EdTech Platform

[![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-35495E?style=for-the-badge&logo=vue.js&logoColor=4FC08D)](https://vuejs.org/)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com/)

SchoolTry is an educational technology platform that leverages AI to enhance the learning experience. This application allows students to ask questions about their lessons and receive AI-generated responses, making learning more interactive and engaging.

## 🚀 Features

- **Interactive Lessons**: Browse and view educational content
- **AI-Powered Q&A**: Get instant answers to your questions
- **User Authentication**: Secure login and registration system
- **Admin Dashboard**: Manage lessons and content with ease
- **Responsive Design**: Works on desktop and mobile devices

## 🤖 AI Integration

This application integrates with the following AI services:

- **Hugging Face** - For natural language processing and question-answering capabilities
- **OpenRouter** - For accessing various AI models including GPT-3.5 and Claude

The AI analyzes lesson content and provides contextual, accurate responses to student questions, enhancing the learning experience.

## 🛠️ Tech Stack

- **Backend**: Laravel 10.x
- **Frontend**: Vue.js 3, Inertia.js
- **Styling**: Tailwind CSS
- **Database**: SQLite (can be configured for MySQL/PostgreSQL)
- **AI Services**: Hugging Face, OpenRouter
- **Deployment**: Ready for Laravel Forge, Vercel, or traditional hosting

## 📋 Prerequisites

- PHP 8.1 or higher
- Composer
- Node.js 16+ & NPM
- SQLite (or MySQL/PostgreSQL)
- Hugging Face API Key (optional)
- OpenRouter API Key (optional)

## 🚀 Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/PappyZero/schooltry-edtech.git
   cd schooltry-edtech
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install JavaScript dependencies**
   ```bash
   npm install
   ```

4. **Create and configure .env file**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure database**
   ```sqlite
   touch database/database.sqlite
   ```
   Update `.env` with your database configuration.

6. **Run migrations and seeders**
   ```bash
   php artisan migrate --seed
   ```

7. **Compile assets**
   ```bash
   npm run build
   ```

8. **Start the development server**
   ```bash
   php artisan serve
   ```

9. **Access the application**
   Open your browser and visit: `http://localhost:8000`

## 🔐 Default Admin Account

- **Email**: admin@example.com
- **Password**: password

## 🌐 Environment Variables

Create a `.env` file and configure the following variables:

```env
APP_NAME="SchoolTry Learning Assistant"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Database Configuration
DB_CONNECTION=sqlite
DB_DATABASE=/path/to/your/database.sqlite

# AI Configuration
HUGGINGFACE_API_KEY=your_huggingface_api_key_here
HUGGINGFACE_MODEL=google/flan-t5-base
OPENROUTER_API_KEY=your_openrouter_api_key_here
OPENROUTER_MODEL=openai/gpt-3.5-turbo
```

## 🧪 Testing

Run the test suite with:

```bash
php artisan test
```

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

Distributed under the MIT License. See `LICENSE` for more information.

## 📧 Contact

Your Name - [@your_twitter](https://twitter.com/your_username) - your.email@example.com

Project Link: [https://github.com/PappyZero/schooltry-edtech](https://github.com/PappyZero/schooltry-edtech)

## 🙏 Acknowledgments

- [Laravel](https://laravel.com)
- [Vue.js](https://vuejs.org/)
- [Inertia.js](https://inertiajs.com/)
- [Tailwind CSS](https://tailwindcss.com/)
- [Hugging Face](https://huggingface.co/)
- [OpenRouter](https://openrouter.ai/)
