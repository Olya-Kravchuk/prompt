## Block Prompt

Der **Block Prompt** ist ein Moodle-Plugin, das es Kursteilnehmern ermöglicht, Fragen zu den Kursinhalten zu stellen und Antworten über die angebundene RAG-Anwendung zu erhalten.

### Voraussetzungen

Der Block Prompt ist vom Plugin **TURI RAG** abhängig. Damit der Block ordnungsgemäß funktioniert, muss TURI RAG zuerst installiert und konfiguriert werden.

### Installation und Konfiguration

1. **TURI RAG installieren:** Installiere zunächst das Plugin TURI RAG in Moodle.
2. **Verbindung konfigurieren:** Konfiguriere den Host und den Port, über die der Block die FastAPI-Anwendung erreicht.
3. **RAG-Profil erstellen:** Erstelle und konfiguriere ein RAG-Profil in TURI RAG.
4. **Block aktivieren:** Aktiviere die Prompt-Funktion für den gewünschten Kurs über die entsprechende Konfiguration in TURI RAG.

### Funktionsweise

Sobald TURI RAG installiert und konfiguriert ist, ein RAG-Profil erstellt wurde und die Prompt-Funktion für einen Kurs aktiviert ist, wird der Block Prompt in diesem Kurs angezeigt.

Der Block ermöglicht es den Nutzern, Fragen einzugeben und Antworten zu erhalten, die von der angebundenen RAG-Anwendung verarbeitet werden.

**Wichtig:** Ohne eine vorherige Installation und Konfiguration von TURI RAG kann der Block Prompt nicht verwendet werden.