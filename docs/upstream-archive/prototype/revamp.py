import os
import re
from bs4 import BeautifulSoup

directory = r"d:\laragon\www\firmanaffiliate\prototype"

head_template = """
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{title}</title>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Icons -->
  <script src="https://unpkg.com/lucide@latest"></script>

  <!-- Bootstrap CSS for Layout preservation -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Configuration -->
  <script>
    tailwind.config = {{
      theme: {{
        extend: {{
          fontFamily: {{
            sans: ['Inter', 'sans-serif'],
          }},
          colors: {{
            primary: {{
              50: '#eef2ff',
              100: '#e0e7ff',
              500: '#6366f1',
              600: '#4f46e5',
              700: '#4338ca',
              800: '#3730a3',
              900: '#312e81',
            }},
            secondary: {{
              50: '#f0fdfa',
              500: '#14b8a6',
              600: '#0d9488',
              900: '#134e4a',
            }},
            accent: {{
              500: '#f59e0b',
              600: '#d97706',
            }},
            slate: {{
              850: '#1e293b',
              900: '#0f172a',
            }}
          }},
          animation: {{
            'float': 'float 6s ease-in-out infinite',
            'float-delayed': 'float 6s ease-in-out 3s infinite',
            'blob': 'blob 7s infinite',
          }},
          keyframes: {{
            float: {{
              '0%, 100%': {{ transform: 'translateY(0)' }},
              '50%': {{ transform: 'translateY(-20px)' }},
            }},
            blob: {{
              '0%': {{ transform: 'translate(0px, 0px) scale(1)' }},
              '33%': {{ transform: 'translate(30px, -50px) scale(1.1)' }},
              '66%': {{ transform: 'translate(-20px, 20px) scale(0.9)' }},
              '100%': {{ transform: 'translate(0px, 0px) scale(1)' }},
            }}
          }}
        }}
      }}
    }}
  </script>

  <!-- Custom CSS Overrides (Tailwind inside Bootstrap) -->
  <style type="text/tailwindcss">
    @layer components {{
      body {{ @apply bg-slate-50 text-slate-700 font-sans; }}

      /* Base Bootstrap to Tailwind Mappings */
      .card, .panel-card {{ @apply bg-white rounded-3xl border border-slate-100 shadow-sm hover-lift transition-all overflow-hidden relative; }}
      .card-header {{ @apply bg-transparent border-b border-slate-100 py-4 px-6; }}
      .card-body {{ @apply p-6; }}

      .btn {{ @apply px-6 py-2.5 rounded-full font-bold transition-all border-0 shadow-sm inline-flex items-center justify-center gap-2; }}
      .btn-sm {{ @apply px-4 py-1.5 text-sm; }}
      .btn-primary, .btn-critical {{ @apply ripple bg-primary-600 hover:bg-primary-700 text-white shadow-lg shadow-primary-500/30 hover:-translate-y-1 transform; }}
      .btn-outline-primary {{ @apply bg-white border border-slate-200 text-slate-700 hover:bg-slate-50; }}
      .btn-light {{ @apply bg-slate-100 text-slate-700 hover:bg-slate-200; }}
      
      .form-control, .form-select {{ @apply w-full rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 focus:ring-2 focus:ring-primary-200 focus:border-primary-500 shadow-none transition-all; }}
      .input-group-text {{ @apply bg-slate-50 border-slate-200 text-slate-400 rounded-xl px-4; }}
      .input-group .form-control {{ @apply border-l-0 rounded-l-none pl-0 bg-transparent; }}
      .input-group .input-group-text {{ @apply border-r-0 rounded-r-none pr-3 bg-transparent; }}
      .input-group {{ @apply bg-slate-50 border border-slate-200 rounded-xl focus-within:ring-2 focus-within:ring-primary-200 focus-within:border-primary-500 transition-all overflow-hidden; }}
      .input-group > * {{ @apply border-none; }}
      
      .navbar {{ @apply glass border-b border-slate-100 py-3; }}
      .navbar-brand {{ @apply font-bold text-2xl tracking-tight text-slate-900; }}
      .navbar-brand i {{ @apply text-primary-600; }}
      
      h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6 {{ @apply font-bold text-slate-900; }}
      .text-muted, .text-secondary {{ @apply text-slate-500; }}
      .bg-soft {{ @apply bg-slate-50; }}
      .text-brand, .text-primary, .brand-primary {{ @apply text-primary-600; }}
      .shadow-soft {{ @apply shadow-sm; }}
    }}

    /* Globals */
    .glass {{ background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.5); }}
    .hover-lift {{ transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1); }}
    .hover-lift:hover {{ transform: translateY(-8px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); }}
    .ripple {{ position: relative; overflow: hidden; }}
    .ripple::after {{ content: ""; display: block; position: absolute; width: 100%; height: 100%; top: 0; left: 0; pointer-events: none; background-image: radial-gradient(circle, #fff 10%, transparent 10.01%); background-repeat: no-repeat; background-position: 50%; transform: scale(10, 10); opacity: 0; transition: transform .5s, opacity 1s; }}
    .ripple:active::after {{ transform: scale(0, 0); opacity: 0.3; transition: 0s; }}
    .text-gradient {{ background-clip: text; -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-image: linear-gradient(to right, #4f46e5, #0d9488); }}
  </style>
"""

bg_blobs = """
  <!-- Decorative Abstract Background -->
  <div class="fixed top-0 w-full h-full overflow-hidden z-[-1] pointer-events-none">
    <div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-primary-200 rounded-full mix-blend-multiply filter blur-3xl opacity-40 animate-blob"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-96 h-96 bg-secondary-200 rounded-full mix-blend-multiply filter blur-3xl opacity-40 animate-blob animation-delay-200"></div>
  </div>
"""

for filename in os.listdir(directory):
    if filename.endswith(".html"):
        filepath = os.path.join(directory, filename)
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()

        # Skip files that don't look like they use the old bootstrap theme, e.g. index, course-detail, book-detail
        # But wait, dashboard uses admin-interactive-ui.css. Let's just check if it's not our newly created files.
        if filename in ["index.html", "course-detail.html", "book-detail.html"]:
            continue

        soup = BeautifulSoup(content, 'html.parser')
        head = soup.find('head')
        
        if head:
            title_tag = soup.find('title')
            title_text = title_tag.text.strip() if title_tag else "Firman Pratama Affiliate"
            
            # Form the new head
            new_head = head_template.format(title=title_text)
            
            # Replace the entire head block using regex since bs4 serialization can mess up layout
            # A little trick:
            pass

        # Let's do a strict string replacement finding <head> and </head>
        head_match = re.search(r'<head>(.*?)</head>', content, flags=re.IGNORECASE | re.DOTALL)
        if head_match:
            original_head = head_match.group(1)
            title_match = re.search(r'<title>(.*?)</title>', original_head, flags=re.IGNORECASE)
            title = title_match.group(1) if title_match else "Firman Pratama Affiliate"

            new_head_content = head_template.format(title=title)
            content = re.sub(r'<head>.*?</head>', f'<head>\n{new_head_content}\n</head>', content, flags=re.IGNORECASE | re.DOTALL)
        
        # Add the abstract blobs right after <body ...>
        # Only add if not already added to avoid duplication during testing
        if '<!-- Decorative Abstract Background -->' not in content:
            # find <body ...> or <body>
            body_match = re.search(r'<body[^>]*>', content, flags=re.IGNORECASE)
            if body_match:
                body_tag = body_match.group(0)
                # replace <body class="bg-soft"> with `<body class="relative bg-slate-50">`
                new_body_tag = re.sub(r'class="[^"]*"', 'class="relative bg-slate-50 text-slate-700"', body_tag)
                if 'class=' not in new_body_tag:
                     new_body_tag = new_body_tag.replace('>', ' class="relative bg-slate-50 text-slate-700">')
                
                content = content.replace(body_tag, new_body_tag + "\n" + bg_blobs)

        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Revamped {filename}")

