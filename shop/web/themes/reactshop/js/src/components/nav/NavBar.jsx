import React from "react";
import CategorySelector from "./CategorySelector";
import SearchBar from "./SearchBar";
import CartButton from "./CartButton";
import { useNavigate, useLocation } from "react-router-dom";
import { Link } from "react-router-dom";
import LogoutButton from "./LogoutButton";

import { useSelector } from "react-redux";

function NavBar() {
  const user = useSelector((state) => state.auth.user);
  console.log(user);

  const { pathname } = useLocation();

  const nav = useNavigate();
  const handleHomeNavigation = () => {
    nav("/");
  };

  const handleOnClick = () => {
    nav("login");
  };
  return (
    <nav className="navbar navbar-expand-lg navbar-dark bg-dark border-bottom fixed-top">
      <div className="container-fluid px-md-5">
        <span
          id="name"
          className="navbar-brand fw-bold pointer"
          onClick={handleHomeNavigation}
        >
          Shopping Cart app
        </span>
        <button
          className="navbar-toggler"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#navbarSupportedContent"
        >
          <span className="navbar-toggler-icon"></span>
        </button>
        <div className="navbar-collapse justify-content-end collapse">
          {/* {user ? (
            <>
              <span className="me-2">Welcome, {user.name} </span>
              <LogoutButton />
            </>
          ) : (
            // <Link to="/login">Login</Link>
            <button
              onClick={handleOnClick}
              type="button"
              className="btn btn-outline-success d-md-block mt-3 mt-lg-0 me-2"
            >
              Login
            </button>
          )} */}
          {pathname === "/" && (
            <>
              {" "}
              <CategorySelector />
              <SearchBar />
            </>
          )}

          <CartButton />
        </div>
      </div>
    </nav>
  );
}

export default NavBar;
