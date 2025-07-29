import { useDispatch } from "react-redux";
import { logout } from "../../features/auth/authSlice";
import { useNavigate } from "react-router-dom";

const LogoutButton = () => {
  const dispatch = useDispatch();
  const navigate = useNavigate();

  const handleLogout = () => {
    dispatch(logout());
    navigate("/login");
  };

  return (
    <button
      onClick={handleLogout}
      type="button"
      className="btn btn-outline-success d-md-block mt-3 mt-lg-0 me-2"
    >
      Logout
    </button>
  );
};

export default LogoutButton;
