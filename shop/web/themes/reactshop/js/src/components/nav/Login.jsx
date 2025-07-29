// pages/Login.js
import { useDispatch } from "react-redux";
import { loginSuccess } from "../../features/auth/authSlice";
import { useNavigate } from "react-router-dom";
import { useState } from "react";

const Login = () => {
  const dispatch = useDispatch();
  const navigate = useNavigate();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");

  const handleLoginSubmit = (e) => {
    e.preventDefault();

    // Fake login logic
    if (email === "sachin@gmail.com" && password === "admin") {
      const user = {
        id: 1,
        name: "Sachin Sharma",
        email,
      };
      dispatch(loginSuccess(user));
      navigate("/"); // Redirect after login
    } else {
      alert("Invalid email or password");
    }
  };

  return (
    <div style={{ padding: "2rem" }}>
      <h2>Login</h2>
      <form onSubmit={handleLoginSubmit} style={{ maxWidth: "300px" }}>
        <div>
          <label>Email:</label>
          <br />
          <input
            type="email"
            value={email}
            placeholder="user@example.com"
            onChange={(e) => setEmail(e.target.value)}
            required
            style={{ width: "100%", padding: "8px", margin: "5px 0" }}
          />
        </div>
        <div>
          <label>Password:</label>
          <br />
          <input
            type="password"
            value={password}
            placeholder="password"
            onChange={(e) => setPassword(e.target.value)}
            required
            style={{ width: "100%", padding: "8px", margin: "5px 0" }}
          />
        </div>
        <button
          type="submit"
          style={{ padding: "10px", width: "100%", marginTop: "10px" }}
        >
          Login
        </button>
      </form>
    </div>
  );
};

export default Login;
