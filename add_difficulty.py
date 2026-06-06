import json
import random

# Đọc file JSON
with open('data/questions.json', 'r', encoding='utf-8') as f:
    questions = json.load(f)

# Danh sách các từ khóa gợi ý độ khó
easy_keywords = ['bao nhiêu', 'là gì', 'tính', 'vectơ nào', 'ma trận nào', 'hàm số nào', 
                 'độ dài', 'vết', 'chiều', 'vectơ không', 'đơn vị', 'công thức']
medium_keywords = ['đạo hàm', 'nguyên hàm', 'tích phân', 'giới hạn', 'cực trị', 
                   'vuông góc', 'đồng biến', 'nghịch biến', 'chuyển vị', 'nghịch đảo']
hard_keywords = ['phân kỳ', 'hạng', 'độc lập tuyến tính', 'giá trị riêng', 'Fourier', 
                 'suy biến', 'determinant', 'det(', 'r(A)', 'tích vô hướng', 'tích có hướng']

# Gán độ khó cho từng câu hỏi
for q in questions:
    question_text = q['question'].lower()
    
    # Đếm số từ khóa xuất hiện
    easy_count = sum(1 for kw in easy_keywords if kw in question_text)
    medium_count = sum(1 for kw in medium_keywords if kw in question_text)
    hard_count = sum(1 for kw in hard_keywords if kw in question_text)
    
    # Gán độ khó dựa trên từ khóa
    if hard_count >= 2:
        q['difficulty'] = 'hard'
    elif medium_count >= 2:
        q['difficulty'] = 'medium'
    elif easy_count >= 2:
        q['difficulty'] = 'easy'
    else:
        # Phân bổ ngẫu nhiên nếu không có từ khóa rõ ràng
        rand = random.random()
        if rand < 0.30:
            q['difficulty'] = 'hard'
        elif rand < 0.65:
            q['difficulty'] = 'medium'
        else:
            q['difficulty'] = 'easy'

# Lưu lại file JSON
with open('data/questions.json', 'w', encoding='utf-8') as f:
    json.dump(questions, f, ensure_ascii=False, indent=4)

print(f"✅ Đã thêm difficulty cho {len(questions)} câu hỏi!")

# Đếm thống kê
easy_count = sum(1 for q in questions if q['difficulty'] == 'easy')
medium_count = sum(1 for q in questions if q['difficulty'] == 'medium')
hard_count = sum(1 for q in questions if q['difficulty'] == 'hard')

print(f"🟢 Dễ: {easy_count} câu")
print(f"🟡 Trung bình: {medium_count} câu")
print(f"🔴 Khó: {hard_count} câu")