import React from "react";
import { useNavigate } from "react-router-dom";

function NoContent(props) {
  const { text, btnText } = props;
  const nav = useNavigate();
  const handleHomeNavigation = () => {
    nav("/");
  };
  return (
    <div className="text-white text-center my-5 mx-auto p-0 p-md-5 rounded">
      <h2>{text}</h2>
      <button className="btn btn-success btn-lg" onClick={handleHomeNavigation}>
        {btnText}
      </button>
    </div>
  );
}

export default NoContent;
