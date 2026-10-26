import wave
import numpy as np

rate = 16000
duration = 3.0
t = np.linspace(0, duration, int(rate * duration), endpoint=False)
signal = (0.3 * np.sin(2 * np.pi * 440 * t) * 32767).astype(np.int16)

path = r"C:\wamp64\www\projet hackaton\ai-knowledge-workspace\_test_audio.wav"
with wave.open(path, "w") as handle:
    handle.setnchannels(1)
    handle.setsampwidth(2)
    handle.setframerate(rate)
    handle.writeframes(signal.tobytes())

print("wav cree")
